<?php

namespace Modules\ManajemenMahasiswa\Services;

use Illuminate\Support\Facades\DB;
use Modules\ManajemenMahasiswa\Models\Vote;
use Modules\ManajemenMahasiswa\Services\GamificationService;

class VoteService
{
    public function __construct(private GamificationService $gamificationService) {}

    /**
     * Memberikan Upvote (+1) ke suatu entitas (misal: Discussion atau Comment).
     */
    public function upvote(int $userId, string $voteableType, int $voteableId): void
    {
        $this->castVote($userId, $voteableType, $voteableId, 1);
    }

    /**
     * Memberikan Downvote (-1) ke suatu entitas.
     */
    public function downvote(int $userId, string $voteableType, int $voteableId): void
    {
        $this->castVote($userId, $voteableType, $voteableId, -1);
    }

    /**
     * Mencabut vote yang sudah diberikan sebelumnya.
     */
    public function unvote(int $userId, string $voteableType, int $voteableId): void
    {
        DB::transaction(function () use ($userId, $voteableType, $voteableId) {
            /** @var Vote|null $existingVote */
            $existingVote = Vote::where('user_id', $userId)
                ->where('voteable_type', $voteableType)
                ->where('voteable_id', $voteableId)
                ->first();

            if ($existingVote) {
                // Perbarui total cached votes di tabel entitas tsb
                $this->updateCachedVoteCount($voteableType, $voteableId, -$existingVote->value);
                
                // Jika dulunya adalah upvote, cabut XP si penerima
                if ($existingVote->value === 1) {
                    $this->handleXpReversion($voteableType, $voteableId);
                }

                $existingVote->delete();
            }
        });
    }

    /**
     * Logika utama untuk memberikan suara (vote).
     */
    private function castVote(int $userId, string $voteableType, int $voteableId, int $value): void
    {
        DB::transaction(function () use ($userId, $voteableType, $voteableId, $value) {
            /** @var Vote|null $existingVote */
            $existingVote = Vote::where('user_id', $userId)
                ->where('voteable_type', $voteableType)
                ->where('voteable_id', $voteableId)
                ->first();

            if ($existingVote) {
                if ($existingVote->value === $value) {
                    // Jika tipe vote tidak berubah (sudah pernah upvote dan mengupvote ulang), tidak melakukan apa-apa
                    return;
                }

                // Jika ubah pilihan dari +1 jadi -1, gap perubahannya adalah -2
                $netChange = $value - $existingVote->value;
                $existingVote->update(['value' => $value]);

                $this->updateCachedVoteCount($voteableType, $voteableId, $netChange);
                
                if ($value === 1) {
                    $this->handleXpAward($voteableType, $voteableId);
                } else {
                    $this->handleXpReversion($voteableType, $voteableId);
                }

            } else {
                // Vote Baru
                Vote::create([
                    'user_id'       => $userId,
                    'voteable_type' => $voteableType,
                    'voteable_id'   => $voteableId,
                    'value'         => $value,
                ]);

                $this->updateCachedVoteCount($voteableType, $voteableId, $value);

                if ($value === 1) {
                    $this->handleXpAward($voteableType, $voteableId);
                }
            }
        });
    }

    /**
     * Mengupdate field `vote_count` pada model target secara instan.
     */
    private function updateCachedVoteCount(string $voteableType, int $voteableId, int $netChange): void
    {
        if (class_exists($voteableType)) {
            $model = $voteableType::find($voteableId);
            // Hanya increment jika entitas tersebut punya column "vote_count"
            if ($model && array_key_exists('vote_count', $model->getAttributes())) {
                $model->increment('vote_count', $netChange);
            }
        }
    }

    /**
     * Hadiahkan XP ke pemilik target yang suaranya di-Upvote.
     */
    private function handleXpAward(string $voteableType, int $voteableId): void
    {
        if (class_exists($voteableType)) {
            $model = $voteableType::find($voteableId);
            if ($model && $model->user_id) {
                // Penerima bukan di-harkot jika ia yang ngevote sendiri
                $this->gamificationService->addXp(
                    $model->user_id,
                    'received_upvote',
                    'Kontribusi Mendapat Upvote',
                    GamificationService::XP_VOTE_UP,
                    $voteableId
                );
            }
        }
    }

    /**
     * Tarik balik saldo XP pemilik dari target ketika suaranya dihapus / dirubah ke downvote.
     */
    private function handleXpReversion(string $voteableType, int $voteableId): void
    {
        if (class_exists($voteableType)) {
            $model = $voteableType::find($voteableId);
            if ($model && $model->user_id) {
                $this->gamificationService->addXp(
                    $model->user_id,
                    'lost_upvote',
                    'Kontribusi Kehilangan Upvote',
                    -GamificationService::XP_VOTE_UP, // Memberikan XP negatif (penalti saldo)
                    $voteableId
                );
            }
        }
    }
}
