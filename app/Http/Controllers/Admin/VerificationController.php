<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TalentProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class VerificationController extends Controller
{
    /**
     * Display a listing of talent verification requests.
     */
    public function index(Request $request)
    {
        $statusFilter = $request->query('status', 'Semua');
        $categoryFilter = $request->query('category', 'Semua');
        $searchQuery = $request->query('q', '');

        // Base query with eager loading user and reviewer
        $query = TalentProfile::with(['user', 'reviewer']);

        // Apply search query
        if (!empty($searchQuery)) {
            $query->where(function ($q) use ($searchQuery) {
                $q->where('institution', 'like', "%{$searchQuery}%")
                  ->orWhere('major', 'like', "%{$searchQuery}%")
                  ->orWhere('city', 'like', "%{$searchQuery}%")
                  ->orWhere('document_original_name', 'like', "%{$searchQuery}%")
                  ->orWhere('document', 'like', "%{$searchQuery}%")
                  ->orWhereHas('user', function ($uq) use ($searchQuery) {
                      $uq->where('name', 'like', "%{$searchQuery}%")
                         ->orWhere('email', 'like', "%{$searchQuery}%");
                  });
            });
        }

        // Apply category filter
        if ($categoryFilter !== 'Semua') {
            $query->where('category', 'like', "%{$categoryFilter}%");
        }

        // Apply status filter (support machine keys and legacy Indonesian strings)
        if ($statusFilter !== 'Semua') {
            if ($statusFilter === 'pending' || $statusFilter === 'Menunggu Verifikasi') {
                $query->whereIn('verification_status', ['pending', 'Menunggu Verifikasi']);
            } elseif ($statusFilter === 'verified' || $statusFilter === 'Terverifikasi') {
                $query->whereIn('verification_status', ['verified', 'Terverifikasi']);
            } elseif ($statusFilter === 'rejected' || $statusFilter === 'Ditolak') {
                $query->whereIn('verification_status', ['rejected', 'Ditolak']);
            } elseif ($statusFilter === 'expired') {
                $query->where('verification_status', 'expired');
            }
        }

        // Get counts for filter tabs
        $counts = [
            'Semua' => TalentProfile::count(),
            'pending' => TalentProfile::whereIn('verification_status', ['pending', 'Menunggu Verifikasi'])->count(),
            'verified' => TalentProfile::whereIn('verification_status', ['verified', 'Terverifikasi'])->count(),
            'rejected' => TalentProfile::whereIn('verification_status', ['rejected', 'Ditolak'])->count(),
            'expired' => TalentProfile::where('verification_status', 'expired')->count(),
        ];

        // Sort: Pending first, then newest submitted_at, then newest id
        $talents = $query->orderByRaw("CASE WHEN verification_status IN ('pending', 'Menunggu Verifikasi') THEN 0 ELSE 1 END")
                         ->orderByDesc('submitted_at')
                         ->orderByDesc('id')
                         ->get();

        return view('admin.verifikasi', [
            'talents' => $talents,
            'counts' => $counts,
            'currentFilter' => $statusFilter,
            'currentCategory' => $categoryFilter,
            'searchQuery' => $searchQuery,
        ]);
    }

    /**
     * Preview Dokumen PDF Asli dari Storage Private (Aman)
     */
    public function previewDokumen($id)
    {
        $talent = TalentProfile::with('user')->findOrFail($id);

        if (!$talent->document_path || !Storage::disk('local')->exists($talent->document_path)) {
            abort(404, "Berkas dokumen PDF fisik tidak ditemukan untuk talenta {$talent->user->name}. (Data contoh lokal lama atau berkas fisik belum diunggah)");
        }

        $fullPath = Storage::disk('local')->path($talent->document_path);
        $fileName = $talent->document_original_name ?: 'Dokumen_Verifikasi_' . $talent->user->name . '.pdf';

        return response()->file($fullPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }

    /**
     * Approve talent verification.
     */
    public function approve($id)
    {
        $talent = TalentProfile::with('user')->findOrFail($id);

        $talent->update([
            'verification_status' => 'verified',
            'eligibility_status' => 'eligible',
            'rejection_reason' => null,
            'verified_by' => Auth::id(),
            'verified_at' => now(),
        ]);

        return redirect()->back()->with('success', "Akun talenta {$talent->user->name} berhasil disetujui dan berstatus Terverifikasi!");
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
            'verification_status' => 'rejected',
            'rejection_reason' => trim($request->reason),
            'verified_by' => Auth::id(),
            'verified_at' => now(),
        ]);

        return redirect()->back()->with('warning', "Pengajuan talenta {$talent->user->name} telah ditolak dengan alasan yang tercatat.");
    }
}
