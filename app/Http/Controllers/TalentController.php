<?php

namespace App\Http\Controllers;

use App\Exceptions\ApplyNotAllowedException;
use App\Exceptions\CancelNotAllowedException;
use App\Models\Application;
use App\Models\Project;
use App\Models\ProjectApplication;
use App\Models\ProjectPromotion;
use App\Models\ProviderNotification;
use App\Models\TalentProfile;
use App\Services\ProjectApplyService;
use App\Services\TalentEligibilityService;
use App\Services\WorkstyleMatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TalentController extends Controller
{
    protected TalentEligibilityService $eligibilityService;
    protected WorkstyleMatchingService $matchingService;
    protected ProjectApplyService $applyService;

    public function __construct(
        TalentEligibilityService $eligibilityService,
        WorkstyleMatchingService $matchingService,
        ProjectApplyService $applyService
    ) {
        $this->eligibilityService = $eligibilityService;
        $this->matchingService = $matchingService;
        $this->applyService = $applyService;
    }

    public function beranda(): View
    {
        $user = Auth::user();
        $talent = $this->getOrCreateTalentProfile($user);

        $projects = Project::openForTalent()
            ->withApplyCounts()
            ->orderBy('id', 'asc')
            ->get();

        $projects->transform(function ($project) use ($talent) {
            $match = $this->matchingService->calculateMatch($talent, $project);
            $project->match_score = $match['score'] ?? 75;
            $project->match_details = $match['details'] ?? [];
            $project->match_data = $match;
            return $project;
        });

        // Proyek promosi tampil sebagai kartu Featured di atas rekomendasi (tidak diulang di grid).
        [$projects, $featuredProject] = $this->decorateListing($projects);

        $topRecommendations = !empty($talent->assessment_answers)
            ? $projects->sortByDesc('match_score')->take(6)->values()
            : $projects->take(6)->values();

        $totalApplied = Application::where('user_id', $user->id)->count();

        $recentApplications = Application::with('project')
            ->where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        return view('talent.beranda', [
            'reputation'         => app(\App\Services\Rating\RatingService::class)->summaryFor($user, \App\Models\Rating::ROLE_TALENTA),
            'user'               => $user,
            'talent'             => $talent,
            'topRecommendations' => $topRecommendations,
            'recentApplications' => $recentApplications,
            'applyStatuses'      => $this->applyService->getApplyStatuses($featuredProject ? $topRecommendations->concat([$featuredProject]) : $topRecommendations, $user),
            'totalApplied'       => $totalApplied,
            'featuredProject'    => $featuredProject,
            'pageToken'          => Str::random(24),
        ]);
    }

    public function rekomendasi(): View
    {
        $user = Auth::user();
        $talent = $this->getOrCreateTalentProfile($user);

        $projects = Project::openForTalent()
            ->withApplyCounts()
            ->orderBy('id', 'asc')
            ->get();

        $projects->transform(function ($project) use ($talent) {
            $match = $this->matchingService->calculateMatch($talent, $project);
            $project->match_score = $match['score'] ?? 75;
            $project->match_details = $match['details'] ?? [];
            $project->match_data = $match;
            return $project;
        });

        if (!empty($talent->assessment_answers)) {
            $projects = $projects->sortByDesc('match_score')->values();
        }

        [$projects, $featuredProject] = $this->decorateListing($projects);

        return view('talent.rekomendasi', [
            'user' => $user,
            'talent' => $talent,
            'projects' => $projects,
            'applyStatuses' => $this->applyService->getApplyStatuses($featuredProject ? $projects->concat([$featuredProject]) : $projects, $user),
            'featuredProject' => $featuredProject,
            'pageToken' => Str::random(24),
        ]);
    }

    public function cariProyek(Request $request): View
    {
        $user = Auth::user();
        $talent = $this->getOrCreateTalentProfile($user);

        $query = Project::openForTalent()->withApplyCounts();

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('provider_name', 'like', "%{$q}%")
                    ->orWhere('category', 'like', "%{$q}%");
            });
        }

        if ($request->filled('category') && $request->category !== 'Semua') {
            $query->where('category', $request->category);
        }

        if ($request->filled('work_type') && $request->work_type !== 'Semua') {
            $wt = $request->work_type;
            $query->where(function ($sub) use ($wt) {
                $sub->where('work_type', $wt)->orWhere('work_method', strtolower($wt));
            });
        }

        if ($request->filled('location')) {
            $query->where('location', 'like', "%{$request->location}%");
        }

        $projects = $query->orderBy('id', 'asc')->get();

        $projects->transform(function ($project) use ($talent) {
            $match = $this->matchingService->calculateMatch($talent, $project);
            $project->match_score = $match['score'];
            $project->match_data = $match;
            return $project;
        });

        $categories = Project::distinct()->pluck('category')->filter()->values();
        if ($categories->isEmpty()) {
            $categories = collect(['Kreatif', 'Operasional', 'Administrasi', 'Web Development', 'Desain Grafis', 'Digital Marketing']);
        }

        // Promosi hanya untuk proyek yang SUDAH lolos filter/pencarian → tetap relevan.
        [$projects, $featuredProject] = $this->decorateListing($projects);

        return view('talent.cari', [
            'user'              => $user,
            'talent'            => $talent,
            'projects'          => $projects,
            'categories'        => $categories,
            'applyStatuses'     => $this->applyService->getApplyStatuses($featuredProject ? $projects->concat([$featuredProject]) : $projects, $user),
            'filters'           => $request->only(['q', 'category', 'work_type', 'location']),
            'featuredProject'   => $featuredProject,
            'pageToken'         => Str::random(24),
        ]);
    }

    public function lamarProyek(Request $request, $id): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        $talent = $this->getOrCreateTalentProfile($user);

        if ($talent->verification_status !== 'verified' || $talent->eligibility_status !== 'eligible') {
            $message = 'Anda harus menyelesaikan verifikasi status talenta muda terlebih dahulu sebelum dapat melamar proyek.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message, 'reason' => 'not_verified'], 403);
            }

            return redirect()->route('talent.verifikasi')->with('error', $message);
        }

        $project = Project::findOrFail($id);
        $message = $request->input('message', 'Saya siap berkontribusi pada proyek ini.');

        $matchScore = null;
        if (!empty($talent->assessment_answers)) {
            $match = $this->matchingService->calculateMatch($talent, $project);
            $matchScore = $match['score'] ?? null;
        }

        // Semua aturan kuota divalidasi ulang di dalam transaksi + row lock (lihat ProjectApplyService).
        try {
            $projectApplication = $this->applyService->apply($project->id, $user, [
                'message' => $message,
                'match_score' => $matchScore,
                'talent_snapshot' => [
                    'name' => $user->name,
                    'category' => $talent->category,
                    'institution' => $talent->institution,
                    'city' => $talent->city,
                    'bio' => $talent->bio,
                    'competencies' => $talent->skills ?? [],
                    'portfolios' => $talent->portfolios ?? [],
                ],
            ]);
        } catch (ApplyNotAllowedException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'reason' => $e->reason(),
                    'apply_status' => $e->applyStatus,
                ], $e->httpStatus());
            }

            return redirect()->back()->with('warning', $e->getMessage());
        }

        if ($project->user_id) {
            ProviderNotification::create([
                'user_id' => $project->user_id,
                'title' => "Pelamar baru pada {$project->title}",
                'message' => "{$user->name} telah mengajukan lamaran untuk proyek \"{$project->title}\".",
                'type' => 'applicant_new',
                'target_url' => '/penyedia/pelamar',
                'is_read' => false,
            ]);
        }

        // Atribusi Sponsored Project: hanya bila klik promosi tercatat di server ≤ 24 jam sebelumnya.
        app(\App\Services\PromotionTrackingService::class)->attributeApplication($projectApplication, $user, $request->session());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Lamaran Anda berhasil dikirim ke penyedia proyek!',
                'apply_status' => $this->applyService->getApplyStatus($project->fresh(), $user),
            ], 201);
        }

        return redirect()->route('talent.lamaran')->with('success', 'Lamaran Anda berhasil dikirim ke penyedia proyek!');
    }

    /**
     * Status pendaftaran terkini untuk modal detail proyek (di-refetch saat modal dibuka).
     */
    public function statusLamaran($id): JsonResponse
    {
        $project = Project::findOrFail($id);

        return response()->json($this->applyService->getApplyStatus($project, Auth::user()));
    }

    public function lamaranSaya(Request $request): View
    {
        $user = Auth::user();
        $talent = $this->getOrCreateTalentProfile($user);
        $currentTab = $request->query('tab', 'Semua');

        $query = Application::with('project')->where('user_id', $user->id);

        if ($currentTab !== 'Semua') {
            $query->where('status', $currentTab);
        }

        $applications = $query->latest()->get();

        // Status sumber utama (project_applications) menentukan lamaran mana yang boleh dibatalkan.
        $cancellableProjectIds = ProjectApplication::where('user_id', $user->id)
            ->whereIn('status', ProjectApplication::CANCELLABLE_STATUSES)
            ->pluck('project_id')
            ->all();

        return view('talent.lamaran', [
            'user' => $user,
            'talent' => $talent,
            'applications' => $applications,
            'currentTab' => $currentTab,
            'cancellableProjectIds' => $cancellableProjectIds,
            'cancelStatus' => $this->applyService->getCancelStatus($user),
        ]);
    }

    /**
     * Talenta membatalkan lamarannya sendiri (maks. 3 kali per 7 hari). Setelah dibatalkan,
     * talenta tidak bisa mengajukan lagi ke proyek yang sama.
     */
    public function batalkanLamaran(Request $request, $id): RedirectResponse|JsonResponse
    {
        $user = Auth::user();

        try {
            $application = $this->applyService->cancel((int) $id, $user);
        } catch (CancelNotAllowedException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'reason' => $e->reason,
                    'cancel_status' => $e->cancelStatus ?: $this->applyService->getCancelStatus($user),
                ], $e->httpStatus);
            }

            return redirect()->back()->with('warning', $e->getMessage());
        }

        $project = Project::withTrashed()->find($application->project_id);
        if ($project?->user_id) {
            ProviderNotification::create([
                'user_id' => $project->user_id,
                'title' => "Lamaran dibatalkan pada {$project->title}",
                'message' => "{$user->name} membatalkan lamarannya untuk proyek \"{$project->title}\". Slot pelamar terbuka kembali.",
                'type' => 'applicant_cancelled',
                'target_url' => '/penyedia/pelamar',
                'is_read' => false,
            ]);
        }

        $cancelStatus = $this->applyService->getCancelStatus($user);
        $message = 'Lamaran berhasil dibatalkan. Anda tidak dapat mengajukan lagi ke proyek ini. '
            . "Sisa pembatalan 7 hari terakhir: {$cancelStatus['remaining']} dari {$cancelStatus['limit']}.";

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'cancel_status' => $cancelStatus]);
        }

        return redirect()->route('talent.lamaran')->with('success', $message);
    }

    public function profil(): View
    {
        $user = Auth::user();
        $talent = $this->getOrCreateTalentProfile($user);

        return view('talent.profil', [
            'user' => $user,
            'talent' => $talent,
            'reputation' => app(\App\Services\Rating\RatingService::class)->summaryFor($user, \App\Models\Rating::ROLE_TALENTA),
        ]);
    }

    public function updateProfil(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $talent = $this->getOrCreateTalentProfile($user);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'institution' => 'nullable|string|max:255',
            'major' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'bio' => 'nullable|string|max:1000',
            'availability' => 'nullable|string|max:50',
            'work_mode' => 'nullable|string|max:50',
            'skills' => 'nullable',
        ]);

        $user->update(['name' => $validated['name']]);

        $skills = $validated['skills'];
        if (is_string($skills)) {
            $skills = array_filter(array_map('trim', explode(',', $skills)));
        }

        $talent->update([
            'institution' => $validated['institution'] ?? $talent->institution,
            'major' => $validated['major'] ?? $talent->major,
            'city' => $validated['city'] ?? $talent->city,
            'bio' => $validated['bio'] ?? $talent->bio,
            'availability' => $validated['availability'] ?? $talent->availability,
            'work_mode' => $validated['work_mode'] ?? $talent->work_mode,
            'skills' => $skills,
        ]);

        return redirect()->back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function tambahPortofolio(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $talent = $this->getOrCreateTalentProfile($user);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'url' => 'nullable|url:http,https|max:500',
            'image' => 'nullable|url:http,https|max:500',
        ], [
            'url.url' => 'Link karya harus berupa URL yang valid (diawali http:// atau https://).',
            'image.url' => 'URL gambar harus berupa URL yang valid (diawali http:// atau https://).',
        ]);

        $portfolios = $talent->portfolios ?? [];
        $portfolios[] = [
            'id' => (string) Str::uuid(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? '',
            'url' => $validated['url'] ?? '',
            'image' => $validated['image'] ?? '',
            'created_at' => now()->toDateTimeString(),
        ];

        $talent->update(['portfolios' => $portfolios]);

        return redirect()->back()->with('success', 'Portofolio berhasil ditambahkan.');
    }

    public function ubahPortofolio(Request $request, $id): RedirectResponse
    {
        $user = Auth::user();
        $talent = $this->getOrCreateTalentProfile($user);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'url' => 'nullable|url:http,https|max:500',
            'image' => 'nullable|url:http,https|max:500',
        ], [
            'url.url' => 'Link karya harus berupa URL yang valid (diawali http:// atau https://).',
            'image.url' => 'URL gambar harus berupa URL yang valid (diawali http:// atau https://).',
        ]);

        $found = false;
        $portfolios = array_map(function ($item) use ($id, $validated, &$found) {
            if ((string) ($item['id'] ?? '') !== (string) $id) {
                return $item;
            }
            $found = true;

            return array_merge($item, [
                'title' => $validated['title'],
                'description' => $validated['description'] ?? '',
                'url' => $validated['url'] ?? '',
                'image' => $validated['image'] ?? '',
                'updated_at' => now()->toDateTimeString(),
            ]);
        }, $talent->portfolios ?? []);

        if (! $found) {
            return redirect()->back()->with('error', 'Item portofolio tidak ditemukan.');
        }

        $talent->update(['portfolios' => $portfolios]);

        return redirect()->back()->with('success', 'Portofolio berhasil diperbarui.');
    }

    public function hapusPortofolio($id): RedirectResponse
    {
        $user = Auth::user();
        $talent = $this->getOrCreateTalentProfile($user);

        $portfolios = array_values(array_filter($talent->portfolios ?? [], function ($item) use ($id) {
            return (string) ($item['id'] ?? '') !== (string) $id;
        }));

        $talent->update(['portfolios' => $portfolios]);

        return redirect()->back()->with('success', 'Portofolio berhasil dihapus.');
    }

    public function verifikasi(): View
    {
        $user = Auth::user();
        $talent = $this->getOrCreateTalentProfile($user);

        return view('talent.verifikasi', [
            'user' => $user,
            'talent' => $talent,
        ]);
    }

    public function ajukanVerifikasi(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $talent = $this->getOrCreateTalentProfile($user);

        if ($talent->verification_status === 'pending') {
            return redirect()->back()->with('warning', 'Pengajuan verifikasi Anda sedang dalam proses pemeriksaan admin.');
        }

        $validated = $request->validate([
            'category' => 'required|string',
            'institution' => 'required|string|max:255',
            'major' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'document' => [
                'required',
                'file',
                'mimes:pdf',
                'max:2048',
            ],
            'class_level' => 'nullable|string',
            'academic_year' => 'nullable|string',
            'graduation_year' => 'nullable|numeric',
            'entry_year' => 'nullable|numeric',
            'current_semester' => 'nullable|numeric',
            'graduation_month' => 'nullable|numeric',
        ], [
            'document.required' => 'Dokumen pendukung (PDF) wajib diunggah.',
            'document.mimes' => 'Format berkas dokumen harus berupa file PDF.',
            'document.max' => 'Ukuran berkas dokumen tidak boleh melebihi 2 MB (2048 KB).',
            'institution.required' => 'Nama institusi pendidikan wajib diisi.',
            'major.required' => 'Jurusan / program studi wajib diisi.',
            'city.required' => 'Kota institusi wajib diisi.',
        ]);

        $screening = $this->eligibilityService->evaluateEligibility($validated['category'], $request->all());

        if (!$screening['is_eligible']) {
            $talent->update([
                'category' => $validated['category'],
                'institution' => $validated['institution'],
                'major' => $validated['major'],
                'city' => $validated['city'],
                'eligibility_status' => 'ineligible',
                'verification_status' => 'rejected',
                'rejection_reason' => $screening['notes'] ?? 'Tidak memenuhi kriteria kelayakan talenta muda.',
                'submitted_at' => now(),
            ]);

            return redirect()->back()->with('error', $screening['notes'] ?? 'Data yang Anda masukkan tidak memenuhi kriteria kelayakan.');
        }

        $file = $request->file('document');
        $originalName = $file->getClientOriginalName();
        $sizeKb = round($file->getSize() / 1024);

        $filename = 'doc_' . $user->id . '_' . Str::random(16) . '.pdf';
        $path = $file->storeAs('talent_documents', $filename, 'local');

        $talent->update([
            'category' => $validated['category'],
            'institution' => $validated['institution'],
            'major' => $validated['major'],
            'city' => $validated['city'],
            'document' => $originalName,
            'document_path' => $path,
            'document_original_name' => $originalName,
            'document_size' => $sizeKb,
            'eligibility_status' => 'eligible',
            'verification_status' => 'pending',
            'screening_notes' => $screening['notes'] ?? 'Lolos pemeriksaan kelayakan awal. Menunggu verifikasi dokumen oleh admin.',
            'rejection_reason' => null,
            'submitted_at' => now(),
            'class_level' => $validated['class_level'] ?? null,
            'academic_year' => $validated['academic_year'] ?? null,
            'graduation_year' => $validated['graduation_year'] ?? null,
            'entry_year' => $validated['entry_year'] ?? null,
            'current_semester' => $validated['current_semester'] ?? null,
            'graduation_month' => $validated['graduation_month'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Pengajuan verifikasi berhasil dikirim dan lolos screening awal! Dokumen Anda sedang menunggu pemeriksaan tim admin.');
    }

    public function previewDokumen(): StreamedResponse|RedirectResponse
    {
        $user = Auth::user();
        $talent = $this->getOrCreateTalentProfile($user);

        if (!$talent->document_path || !Storage::disk('local')->exists($talent->document_path)) {
            return redirect()->back()->with('error', 'Dokumen PDF belum tersedia atau berkas fisik tidak ditemukan.');
        }

        return Storage::disk('local')->response(
            $talent->document_path,
            $talent->document_original_name ?? 'Dokumen_Verifikasi.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    public function asesmen(): View
    {
        $user = Auth::user();
        $talent = $this->getOrCreateTalentProfile($user);

        $questions = WorkstyleMatchingService::QUESTIONS;
        $summary = !empty($talent->assessment_answers)
            ? $this->matchingService->generateSummary($talent->assessment_answers)
            : 'Belum ada data asesmen tersimpan.';

        return view('talent.asesmen', [
            'user' => $user,
            'talent' => $talent,
            'questions' => $questions,
            'summary' => $summary,
        ]);
    }

    public function simpanAsesmen(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $talent = $this->getOrCreateTalentProfile($user);

        $rules = [];
        $messages = [];
        foreach (WorkstyleMatchingService::QUESTIONS as $i => $q) {
            $rules["answers.{$q['key']}"] = ['required', 'integer', 'between:1,5'];
            $messages["answers.{$q['key']}.required"] = 'Pertanyaan ' . ($i + 1) . ' belum dijawab.';
        }
        $validated = $request->validate($rules, $messages);

        $talent->update([
            'assessment_answers' => WorkstyleMatchingService::normalizeAnswers($validated['answers']),
            'assessment_completed_at' => now(),
        ]);

        return redirect()->route('talent.asesmen')->with('success', 'Asesmen gaya kerja berhasil disimpan! Skor kecocokan proyek kini memperhitungkan gaya kerjamu.');
    }

    /**
     * Lengkapi daftar proyek Talenta (beranda, rekomendasi, cari) dengan:
     *  - promotion_id untuk proyek yang sedang dipromosikan,
     *  - satu Featured Sponsored Project (dipilih acak dari promosi aktif di daftar ini → rotasi adil),
     *    dikeluarkan dari daftar agar kartu yang sama tidak tampil dua kali,
     *  - rating Penyedia (provider_rating / provider_rating_count) dari satu query agregat.
     * Urutan daftar reguler tidak diubah; promosi hanya menambah visibilitas.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: ?Project}
     */
    protected function decorateListing($projects): array
    {
        $livePromotions = ProjectPromotion::forTalent()
            ->whereIn('project_id', $projects->pluck('id'))
            ->get(['id', 'project_id', 'start_at', 'end_at'])
            ->keyBy('project_id');

        $featuredPromotion = $livePromotions->isNotEmpty() ? $livePromotions->random() : null;
        $featuredProject = $featuredPromotion ? $projects->firstWhere('id', $featuredPromotion->project_id) : null;

        if ($featuredProject) {
            $projects = $projects->reject(fn ($p) => $p->id === $featuredProject->id)->values();
        }

        // Sumber sama dengan profil & syarat promosi: hanya rating valid dari Talenta.
        $providerRatings = app(\App\Services\Rating\RatingService::class)->averagesFor(
            $projects->pluck('user_id')->push($featuredProject?->user_id)->filter()->unique()->values(),
            \App\Models\Rating::ROLE_PENYEDIA
        );

        $decorate = function ($project) use ($livePromotions, $providerRatings) {
            $project->promotion_id = $livePromotions[$project->id]->id ?? null;
            $rating = $providerRatings[$project->user_id] ?? null;
            $project->provider_rating = $rating['average'] ?? null;
            $project->provider_rating_count = $rating['count'] ?? 0;
            return $project;
        };
        $projects->transform($decorate);
        if ($featuredProject) {
            $decorate($featuredProject);
        }

        return [$projects, $featuredProject];
    }

    protected function getOrCreateTalentProfile($user): TalentProfile
    {
        return TalentProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'category' => 'Mahasiswa Aktif',
                'institution' => '-',
                'major' => '-',
                'city' => 'Bandung',
                'verification_status' => 'not_submitted',
                'eligibility_status' => 'undetermined',
            ]
        );
    }
}
