<?php

namespace App\Http\Controllers\Penyedia;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use App\Services\WorkstyleMatchingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    /**
     * Display a listing of the provider's projects.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $status = $request->query('status', 'semua');
        $search = $request->query('search');

        $baseQuery = Project::where('user_id', $user->id);

        // Counts per status for tabs
        $counts = [
            'semua' => (clone $baseQuery)->count(),
            'draft' => (clone $baseQuery)->where('status', Project::STATUS_DRAFT)->count(),
            'published' => (clone $baseQuery)->where('status', Project::STATUS_PUBLISHED)->count(),
            'closed' => (clone $baseQuery)->where('status', Project::STATUS_CLOSED)->count(),
            'archived' => (clone $baseQuery)->where('status', Project::STATUS_ARCHIVED)->count(),
        ];

        $projectsQuery = clone $baseQuery;

        if ($status !== 'semua' && in_array($status, [Project::STATUS_DRAFT, Project::STATUS_PUBLISHED, Project::STATUS_CLOSED, Project::STATUS_ARCHIVED])) {
            $projectsQuery->where('status', $status);
        }

        if (! empty($search)) {
            $projectsQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $projects = $projectsQuery->withCount(['applications', 'acceptedApplications'])->latest()->paginate(10)->withQueryString();

        return view('penyedia.projects.index', [
            'user' => $user,
            'profile' => $user->organizationProfile,
            'projects' => $projects,
            'counts' => $counts,
            'currentStatus' => $status,
            'search' => $search,
        ]);
    }

    /**
     * Show the form for creating a new project.
     */
    public function create(Request $request): View
    {
        return view('penyedia.projects.form', [
            'user' => $request->user(),
            'profile' => $request->user()->organizationProfile,
            'project' => new Project(),
            'isEdit' => false,
        ]);
    }

    /**
     * Store a newly created project in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateProject($request);

        $action = $request->input('action', 'published');
        $status = $action === 'draft' ? Project::STATUS_DRAFT : Project::STATUS_PUBLISHED;

        $project = new Project($validated);
        $project->user_id = $request->user()->id;
        $project->status  = $status;

        // Sync legacy columns so old talent views still work
        $project->work_type   = $validated['work_method'] ?? 'onsite';
        $project->quota       = $validated['talents_needed'] ?? 1;
        $project->deadline    = $validated['registration_deadline'] ?? null;
        $project->schedule    = $validated['availability'] ?? 'Fleksibel';
        $project->workstyle_requirements = WorkstyleMatchingService::characteristicsToRequirements(
            $validated['workstyle_characteristics'] ?? []
        );
        $project->provider_name = $request->user()->organizationProfile?->organization_name
                                  ?? $request->user()->name;

        $project->save();

        $message = $status === Project::STATUS_DRAFT
            ? 'Proyek berhasil disimpan sebagai draf.'
            : 'Proyek berhasil dibuat dan dipublikasikan!';

        return redirect()->route('penyedia.projects.index')->with('success', $message);
    }

    /**
     * Show the form for editing the specified project.
     */
    public function edit(Request $request, Project $project): View
    {
        if ($project->user_id !== $request->user()->id) {
            abort(403, 'Anda tidak berhak mengakses proyek ini.');
        }

        return view('penyedia.projects.form', [
            'user' => $request->user(),
            'profile' => $request->user()->organizationProfile,
            'project' => $project,
            'isEdit' => true,
        ]);
    }

    /**
     * Update the specified project in storage.
     */
    public function update(Request $request, Project $project): RedirectResponse
    {
        if ($project->user_id !== $request->user()->id) {
            abort(403, 'Anda tidak berhak memperbarui proyek ini.');
        }

        $validated = $this->validateProject($request);

        $action = $request->input('action');
        if ($action === 'draft') {
            $validated['status'] = Project::STATUS_DRAFT;
        } elseif ($action === 'published') {
            $validated['status'] = Project::STATUS_PUBLISHED;
        }

        $project->update($validated);

        // Sync legacy columns
        $project->work_type = $validated['work_method'] ?? $project->work_type;
        $project->quota     = $validated['talents_needed'] ?? $project->quota;
        $project->deadline  = $validated['registration_deadline'] ?? $project->deadline;
        $project->schedule  = $validated['availability'] ?? $project->schedule;
        $project->workstyle_requirements = WorkstyleMatchingService::characteristicsToRequirements(
            $validated['workstyle_characteristics'] ?? []
        );
        $project->save();

        return redirect()->route('penyedia.projects.index')
            ->with('success', 'Perubahan proyek berhasil disimpan.');
    }

    /**
     * Update project status (e.g., publish, close, archive).
     */
    public function updateStatus(Request $request, Project $project): RedirectResponse
    {
        if ($project->user_id !== $request->user()->id) {
            abort(403, 'Anda tidak berhak mengubah status proyek ini.');
        }

        $validated = $request->validate([
            'status' => ['required', 'in:draft,published,closed,archived'],
        ]);

        $project->update(['status' => $validated['status']]);

        return redirect()->back()->with('success', 'Status proyek berhasil diperbarui menjadi ' . $project->status_label . '.');
    }

    /**
     * Remove the specified project from storage (soft delete).
     */
    public function destroy(Request $request, Project $project): RedirectResponse
    {
        if ($project->user_id !== $request->user()->id) {
            abort(403, 'Anda tidak berhak menghapus proyek ini.');
        }

        // Business rule: If project has applicants, prevent hard delete or archive instead
        if ($project->applications()->count() > 0) {
            $project->update(['status' => Project::STATUS_ARCHIVED]);
            return redirect()->route('penyedia.projects.index')
                ->with('info', 'Proyek memiliki data pelamar sehingga status dialihkan menjadi Diarsipkan untuk menjaga integritas data.');
        }

        $project->delete();

        return redirect()->route('penyedia.projects.index')
            ->with('success', 'Proyek berhasil dihapus.');
    }

    /**
     * Validate project form fields according to requirements.
     */
    protected function validateProject(Request $request): array
    {
        // Kategori "Lainnya": pakai teks yang diketik penyedia
        if ($request->input('category') === '__lainnya__') {
            $request->merge(['category' => trim((string) $request->input('category_other', ''))]);
        }

        // Radio yang tidak dipilih tidak terkirim; pastikan kosong = tidak ditentukan (bukan nilai lama)
        $request->merge([
            'workstyle_characteristics' => array_filter(
                (array) $request->input('workstyle_characteristics', []),
                fn ($v) => $v !== null && $v !== ''
            ),
        ]);

        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'location' => ['required', 'string', 'max:150'],
            'work_method' => ['required', 'in:onsite,hybrid,remote'],
            'compensation' => ['nullable', 'string', 'max:255'],
            'required_competencies' => ['nullable', 'string', 'max:500'],
            'availability' => ['required', 'string', 'max:100'],
            'talents_needed' => ['required', 'integer', 'min:1', 'max:99'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'registration_deadline' => ['nullable', 'date', 'before_or_equal:start_date'],
            'description' => ['required', 'string', 'max:5000'],
            'responsibilities' => ['nullable', 'string', 'max:3000'],
            'workstyle_characteristics' => ['nullable', 'array'],
            'workstyle_characteristics.*' => ['nullable', 'string', 'max:50'],
        ], [
            'title.required' => 'Judul proyek wajib diisi.',
            'category.required' => 'Kategori proyek wajib dipilih. Jika memilih "Lainnya", ketik nama kategorinya.',
            'location.required' => 'Lokasi proyek wajib diisi.',
            'work_method.required' => 'Metode kerja wajib dipilih.',
            'availability.required' => 'Ketersediaan / jadwal wajib diisi.',
            'talents_needed.required' => 'Jumlah talenta dibutuhkan wajib diisi minimal 1.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'end_date.required' => 'Tanggal selesai wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
            'registration_deadline.before_or_equal' => 'Batas pendaftaran harus sebelum atau sama dengan tanggal mulai.',
            'description.required' => 'Deskripsi proyek wajib diisi.',
        ]);
    }
}
