<?php

namespace Modules\ManajemenMahasiswa\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Modules\ManajemenMahasiswa\Models\XpLog;
use Modules\ManajemenMahasiswa\Models\Badge;
use Modules\ManajemenMahasiswa\Models\UserBadge;
use Modules\ManajemenMahasiswa\Models\Streak;

class GamificationService
{

    // XP Configuration
    public const XP_CREATE_THREAD = 10;
    public const XP_REPLY_THREAD = 5;
    public const XP_BEST_ANSWER = 20;
    public const XP_VOTE_UP = 2;
    public const XP_DAILY_LOGIN = 1;

    // XP Management
    /**
     * Menambahkan XP kepada user dan mencatat log aktivitas.
     */
    public function addXp(int $userId, string $actionName, string $description, int $amount, ?int $referenceId = null): void
    {
        DB::transaction(function () use ($userId, $actionName, $description, $amount, $referenceId) {
            // Catat log penambahan XP
            XpLog::create([
                'user_id' => $userId,
                'action_name' => $actionName,
                'description' => $description,
                'amount' => $amount,
                'reference_id' => $referenceId,
            ]);

            // Cek apabila XP baru membuka badge baru
            $this->checkAndAwardBadges($userId);
        });
    }

    /**
     * Mendapatkan total XP kesuluruhan dari seorang user.
     */
    public function getTotalXp(int $userId): int
    {
        return (int) XpLog::where('user_id', $userId)->sum('amount');
    }

    /**
     * Kalkulasi Level User berdasarkan total XP.
     * Rumus sederhana: Level = floor(sqrt(Total_XP / 10)) + 1
     */
    public function getUserLevel(int $userId): int
    {
        $totalXp = $this->getTotalXp($userId);
        if ($totalXp <= 0)
            return 1;

        return (int) floor(sqrt($totalXp / 10)) + 1;
    }

    // Badges & Achievements
    /**
     * Cek apakah total XP user memenuhi syarat untuk mendapatkan badge baru.
     */
    public function checkAndAwardBadges(int $userId): void
    {
        $totalXp = $this->getTotalXp($userId);

        // Ambil semua badge yang syarat XP-nya sudah tercapai
        $eligibleBadges = Badge::where('required_xp', '<=', $totalXp)->get();

        foreach ($eligibleBadges as $badge) {
            // Gunakan firstOrCreate agar tidak duplikat jika sudah pernah dapat
            UserBadge::firstOrCreate([
                'user_id' => $userId,
                'badge_id' => $badge->id,
            ]);
        }
    }

    /**
     * Mendapatkan daftar badge yang dimiliki oleh user.
     */
    public function getUserBadges(int $userId): Collection
    {
        return UserBadge::with('badge')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get()
            ->pluck('badge');
    }


    // Leaderboard
    /**
     * Mendapatkan top user berdasarkan perolehan XP terbanyak.
     */
    public function getLeaderboard(int $limit = 10): Collection
    {
        return XpLog::selectRaw('user_id, SUM(amount) as total_xp')
            ->groupBy('user_id')
            ->orderByDesc('total_xp')
            ->limit($limit)
            ->with([
                'user' => function ($query) {
                    // Eager load data user (asumsikan menggunakan model User dari App/Models/User)
                    $query->select('id', 'name', 'email');
                }
            ])
            ->get();
    }


    // Daily Streak Logic
    /**
     * Update streak harian user. Dipanggil saat user melakukan aktivitas harian (contoh: login atau buat post).
     */
    public function updateDailyStreak(int $userId): Streak
    {
        return DB::transaction(function () use ($userId) {
            $streak = Streak::firstOrCreate(['user_id' => $userId], [
                'current_streak' => 0,
                'longest_streak' => 0,
                'last_activity_date' => null,
            ]);

            $today = now()->startOfDay();
            $lastActivity = $streak->last_activity_date ? \Carbon\Carbon::parse($streak->last_activity_date)->startOfDay() : null;

            if (!$lastActivity) {
                // Hari pertama kali mulai streak
                $streak->current_streak = 1;
                $streak->longest_streak = 1;
                $streak->last_activity_date = now();
            } elseif ($lastActivity->eq($today->copy()->subDay())) {
                // Berturut-turut pada hari berikutnya
                $streak->current_streak += 1;

                if ($streak->current_streak > $streak->longest_streak) {
                    $streak->longest_streak = $streak->current_streak;
                }

                $streak->last_activity_date = now();
            } elseif ($lastActivity->lt($today->copy()->subDay())) {
                // Streak terputus (jeda lebih dari 1 hari)
                $streak->current_streak = 1;
                $streak->last_activity_date = now();
            }
            // Jika hari yang sama, diamkan saja (tidak ada penambahan streak harian, tapi activity tetap terekam)

            $streak->save();

            // Berikan bonus XP setiap kelipatan 7 hari beruntun (hanya dieksekusi sekali di hari yang sama)
            if ($streak->current_streak > 0 && $streak->current_streak % 7 === 0 && \Carbon\Carbon::parse($streak->last_activity_date)->isToday()) {

                $alreadyBonusLogged = XpLog::where('user_id', $userId)
                    ->where('action_name', 'streak_bonus')
                    ->whereDate('created_at', now()->toDateString())
                    ->exists();

                if (!$alreadyBonusLogged) {
                    $this->addXp($userId, 'streak_bonus', "Bonus Kuat Streak {$streak->current_streak} Hari", 50);
                }
            }

            return $streak;
        });
    }
}
