<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TalentProfile;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    /**
     * Display a listing of talent verification requests.
     */
    public function index(Request $request)
    {
        $statusFilter = $request->query('status', 'Semua');
        $searchQuery = $request->query('q', '');

        // Base query
        $query = TalentProfile::with('user');

        // Apply search query
        if (!empty($searchQuery)) {
            $query->where(function ($q) use ($searchQuery) {
                $q->where('institution', 'like', "%{$searchQuery}%")
                  ->orWhere('category', 'like', "%{$searchQuery}%")
                  ->orWhere('document', 'like', "%{$searchQuery}%")
                  ->orWhereHas('user', function ($uq) use ($searchQuery) {
                      $uq->where('name', 'like', "%{$searchQuery}%")
                         ->orWhere('email', 'like', "%{$searchQuery}%");
                  });
            });
        }

        // Apply status filter
        if ($statusFilter !== 'Semua' && in_array($statusFilter, ['Menunggu Verifikasi', 'Terverifikasi', 'Ditolak'])) {
            $query->where('verification_status', $statusFilter);
        }

        // Get counts for filter pills
        $counts = [
            'Semua' => TalentProfile::count(),
            'Menunggu Verifikasi' => TalentProfile::where('verification_status', 'Menunggu Verifikasi')->count(),
            'Terverifikasi' => TalentProfile::where('verification_status', 'Terverifikasi')->count(),
            'Ditolak' => TalentProfile::where('verification_status', 'Ditolak')->count(),
        ];

        // Sort: Pending first, then newest
        $talents = $query->orderByRaw("CASE WHEN verification_status = 'Menunggu Verifikasi' THEN 0 ELSE 1 END")
                         ->orderByDesc('submitted_at')
                         ->orderByDesc('id')
                         ->get();

        return view('admin.verifikasi', [
            'talents' => $talents,
            'counts' => $counts,
            'currentFilter' => $statusFilter,
            'searchQuery' => $searchQuery,
        ]);
    }

    /**
     * Approve talent verification.
     */
    public function approve($id)
    {
        $talent = TalentProfile::with('user')->findOrFail($id);

        $talent->update([
            'verification_status' => 'Terverifikasi',
            'rejection_reason' => null,
            'verified_at' => now(),
        ]);

        return redirect()->back()->with('success', "Akun talenta {$talent->user->name} berhasil disetujui dan terverifikasi!");
    }

    /**
     * Reject talent verification with reason.
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|min:4|max:500',
        ], [
            'reason.required' => 'Mohon sertakan alasan penolakan agar talenta dapat memperbaikinya.',
            'reason.min' => 'Alasan penolakan minimal 4 karakter.',
        ]);

        $talent = TalentProfile::with('user')->findOrFail($id);

        $talent->update([
            'verification_status' => 'Ditolak',
            'rejection_reason' => trim($request->reason),
            'verified_at' => now(),
        ]);

        return redirect()->back()->with('warning', "Pengajuan talenta {$talent->user->name} telah ditolak.");
    }
}

