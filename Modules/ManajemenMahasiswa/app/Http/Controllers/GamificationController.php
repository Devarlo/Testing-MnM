<?php

namespace Modules\ManajemenMahasiswa\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\ManajemenMahasiswa\Services\GamificationService;

class GamificationController extends Controller
{
    public function __construct(private GamificationService $gamificationService) {}

    /**
     * Tampilkan halaman leaderboard (peringkat) berdasarkan perolehan XP terbanyak
     */
    public function leaderboard()
    {
        // 10 besar user dengan XP terbanyak
        $leaderboard = $this->gamificationService->getLeaderboard(10);
        return view('manajemenmahasiswa::gamification.leaderboard', compact('leaderboard'));
    }

    /**
     * Tampilkan statistik gamifikasi milik user saat ini
     */
    public function myProfile()
    {
        $userId = Auth::id();
        
        $totalXp = $this->gamificationService->getTotalXp($userId);
        $level   = $this->gamificationService->getUserLevel($userId);
        $badges  = $this->gamificationService->getUserBadges($userId);
        
        // Trigger pencatatan daily streak setiap kali mereka membuka halaman profil mereka
        $streak = $this->gamificationService->updateDailyStreak($userId);

        return view('manajemenmahasiswa::gamification.profile', compact(
            'totalXp', 'level', 'badges', 'streak'
        ));
    }
}
