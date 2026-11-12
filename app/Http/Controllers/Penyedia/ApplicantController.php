<?php

namespace App\Http\Controllers\Penyedia;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectApplication;
use App\Models\ProviderNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplicantController extends Controller
{
    /**
     * Display a listing of applicants for the provider's projects.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $status = $request->query('status', 'semua');
        $projectId = $request->query('project_id');
        $search = $request->query('search');

        // Provider's projects
        $myProjects = Project::where('user_id', $user->id)->get();
        $myProjectIds = $myProjects->pluck('id');

        $baseQuery = ProjectApplication::whereIn('project_id', $myProjectIds);

        // Tab counts
        $counts = [
            'semua' => (clone $baseQuery)->count(),
            'menunggu' => (clone $baseQuery)->where('status', ProjectApplication::STATUS_MENUNGGU)->count(),
            'dalam_review' => (clone $baseQuery)->where('status', ProjectApplication::STATUS_DALAM_REVIEW)->count(),
            'diterima' => (clone $baseQuery)->where('status', ProjectApplication::STATUS_DITERIMA)->count(),
            'ditolak' => (clone $baseQuery)->where('status', ProjectApplication::STATUS_DITOLAK)->count(),
        ];

        $applicationsQuery = clone $baseQuery;

        if ($status !== 'semua' && in_array($status, [ProjectApplication::STATUS_MENUNGGU, ProjectApplication::STATUS_DALAM_REVIEW, ProjectApplication::STATUS_DITERIMA, ProjectApplication::STATUS_DITOLAK])) {
            $applicationsQuery->where('status', $status);
        }

        if (! empty($projectId)) {
            $applicationsQuery->where('project_id', $projectId);
        }

        if (! empty($search)) {
            $applicationsQuery->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%");
                })->orWhereHas('project', function ($pq) use ($search) {
                    $pq->where('title', 'like', "%{$search}%");
                });
            });
        }

        $applications = $applicationsQuery->with(['project', 'user.talentProfile'])->latest()->paginate(10)->withQueryString();

        return view('penyedia.applicants.index', [
            'user' => $user,
            'profile' => $user->organizationProfile,
            'applications' => $applications,
            'myProjects' => $myProjects,
            'counts' => $counts,
            'currentStatus' => $status,
            'currentProjectId' => $projectId,
            'search' => $search,
        ]);
    }

    /**
     * Update applicant status (e.g. terima or tolak).
     */
    public function updateStatus(Request $request, ProjectApplication $application): RedirectResponse
    {
        $user = $request->user();

        // Security check: Provider must own the project
        if ($application->project->user_id !== $user->id) {
            abort(403, 'Anda tidak berhak memproses pelamar pada proyek ini.');
        }

        $validated = $request->validate([
            'status' => ['required', 'in:dalam_review,diterima,ditolak'],
        ]);

        $project = $application->project;
        $applicantName = $application->user->name ?? 'Talenta';

        // Business rule: Enforce quota when accepting
        if ($validated['status'] === ProjectApplication::STATUS_DITERIMA) {
            $acceptedCount = $project->acceptedApplications()->where('id', '!=', $application->id)->count();
            if ($acceptedCount >= $project->talents_needed) {
                return redirect()->back()
                    ->with('error', "Gagal menerima pelamar: Kuota talenta untuk proyek \"{$project->title}\" sudah terpenuhi ({$project->talents_needed} orang).");
            }
        }

        $application->update([
            'status' => $validated['status'],
            'reviewed_at' => now(),
        ]);

        // Record provider notification
        $statusText = match ($validated['status']) {
            'diterima' => 'diterima',
            'ditolak' => 'ditolak',
            'dalam_review' => 'masuk tahap review',
            default => $validated['status']
        };

        ProviderNotification::create([
            'user_id' => $user->id,
            'title' => "Status pelamar diperbarui ({$applicantName})",
            'message' => "Pelamar {$applicantName} pada proyek \"{$project->title}\" telah {$statusText}.",
            'type' => 'applicant_status',
            'target_url' => '/penyedia/pelamar',
            'is_read' => true,
            'read_at' => now(),
        ]);

        $alertType = $validated['status'] === 'diterima' ? 'success' : 'info';
        $message = "Status pelamar {$applicantName} berhasil diubah menjadi {$application->status_label}.";

        if ($validated['status'] === ProjectApplication::STATUS_DITERIMA && $project->fresh()->isQuotaFilled()) {
            $message .= " Kuota proyek \"{$project->title}\" kini terpenuhi, sehingga proyek otomatis tidak ditampilkan lagi di katalog talenta.";
        }

        return redirect()->back()->with($alertType, $message);
    }
}
