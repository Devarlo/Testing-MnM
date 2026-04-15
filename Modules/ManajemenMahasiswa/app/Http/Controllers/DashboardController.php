<?php

namespace Modules\ManajemenMahasiswa\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\ManajemenMahasiswa\Models\Pengumuman;
use Modules\ManajemenMahasiswa\Services\DashboardAnalitikService;
use Modules\ManajemenMahasiswa\Services\ForumService;
use Modules\ManajemenMahasiswa\Services\KegiatanService;
use Modules\ManajemenMahasiswa\Services\PengumumanService;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardAnalitikService $dashboardAnalitikService,
        private PengumumanService $pengumumanService,
        private KegiatanService $kegiatanService,
        private ForumService $forumService
    ) {
    }

     public function index()
     {
        $user  = auth()->user();
        $roles = $user->roles->pluck('name')->map(fn($r) => strtolower($r));

        // ── FIX: Cek permission sebelum routing berdasarkan role ──
        if (!$user->can('kemahasiswaan.view')) {
            abort(403, 'Anda tidak memiliki izin akses ke modul Manajemen Mahasiswa (kemahasiswaan.view).');
        }

        if ($roles->intersect(['superadmin', 'admin_kemahasiswaan'])->isNotEmpty()) {
            return app(KemahasiswaanController::class)->adminDashboard();
        }

        if ($roles->contains('dosen')) {
            return app(KemahasiswaanController::class)->dosenDashboard();
        }

        if ($roles->contains('mahasiswa')) {
            return app(KemahasiswaanController::class)->mahasiswaDashboard();
        }

        abort(403, 'Akses Ditolak.');
    }

    private function adminDashboard()
    {
        $stats = $this->dashboardAnalitikService->getSnapshot();
        $kegiatan = $this->kegiatanService->listKegiatan([], 5);
        $pengumuman = $this->pengumumanService->listPublished(Pengumuman::AUDIENCE_ALL, null, 5);

        return view('manajemenmahasiswa::dashboard.admin', compact('stats', 'kegiatan', 'pengumuman'));
    }

    private function dosenDashboard()
    {
        $stats = $this->dashboardAnalitikService->getSnapshot();
        $pengumuman = $this->pengumumanService->listPublished(Pengumuman::AUDIENCE_DOSEN, null, 5);
        $forums = $this->forumService->listForums();

        return view('manajemenmahasiswa::dashboard.dosen', compact('stats', 'pengumuman', 'forums'));
    }
}