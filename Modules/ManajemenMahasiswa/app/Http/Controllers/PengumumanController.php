<?php

namespace Modules\ManajemenMahasiswa\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\ManajemenMahasiswa\Models\Alumni;
use App\Models\Student;
use App\Models\Lecturer;

class MkProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Pass data to view based on roles
        return view('manajemenmahasiswa::profil.index', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'name'  => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $user->update($validated);

        return back()->with('success', 'Profil dasar berhasil diperbarui.');
    }

    public function updateMahasiswa(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'linkedin'   => 'nullable|url|max:255',
            'portofolio' => 'nullable|url|max:255',
        ]);

        if ($user->student) {
            // Update custom columns if they exist, or append to a JSON column
            $user->student->update([
                'linkedin' => $validated['linkedin'],
                'portofolio' => $validated['portofolio']
            ]);
        } else {
             // Fallback to storing in user sso_data or similar if student not initialized
             $ssoData = $user->sso_data ?? [];
             $ssoData['linkedin'] = $validated['linkedin'];
             $ssoData['portofolio'] = $validated['portofolio'];
             $user->update(['sso_data' => $ssoData]);
        }

        return back()->with('success', 'Profil Mahasiswa (Portofolio & LinkedIn) berhasil diperbarui.');
    }

    public function updateAlumni(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'status_posisi_pekerjaan' => 'nullable|string|max:255',
            'profesi'      => 'nullable|string|max:255',
            'instansi'     => 'nullable|string|max:255',
            'linkedin'     => 'nullable|url|max:255',
        ]);

        $alumni = Alumni::where('user_id', $user->id)->first();
        if ($alumni) {
            $alumni->update([
                'status_posisi_pekerjaan' => $validated['status_posisi_pekerjaan'],
                'profesi' => $validated['profesi'],
            ]);
            
            // Simpan linkedin ke user sso_data jika alumni table tidak ada field tsb
            $ssoData = $user->sso_data ?? [];
            $ssoData['linkedin'] = $validated['linkedin'];
            $ssoData['instansi'] = $validated['instansi'];
            $user->update(['sso_data' => $ssoData]);
        }

        return back()->with('success', 'Profil & Karir Alumni berhasil diperbarui.');
    }

    public function updateHimpunan(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'visi'        => 'nullable|string',
            'misi'        => 'nullable|string',
            'struktur'    => 'nullable|string',
            'portofolio'  => 'nullable|url|max:255',
        ]);

        // Simpan data himpunan ke sso_data atau tabel spesifik (PengurusHimaskom) jika ada
        $ssoData = $user->sso_data ?? [];
        $ssoData['himpunan'] = $validated;
        $user->update(['sso_data' => $ssoData]);

        return back()->with('success', 'Profil Organisasi (Visi, Misi, Portofolio) berhasil diperbarui.');
    }
}
