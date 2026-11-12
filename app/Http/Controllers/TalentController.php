<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Project;
use App\Models\ProjectApplication;
use App\Models\ProviderNotification;
use App\Models\TalentProfile;
use App\Services\TalentEligibilityService;
use App\Services\WorkstyleMatchingService;
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

    public function __construct(
        TalentEligibilityService $eligibilityService,
        WorkstyleMatchingService $matchingService
    ) {
        $this->eligibilityService = $eligibilityService;
        $this->matchingService = $matchingService;
    }

    public function beranda(): View
    {
        $user = Auth::user();
        $talent = $this->getOrCreateTalentProfile($user);

        $projects = Project::openForTalent()
            ->orderBy('id', 'asc')
            ->get();

        $projects->transform(function ($project) use ($talent) {
            $match = $this->matchingService->calculateMatch($talent, $project);
            $project->match_score = $match['score'] ?? 75;
            $project->match_details = $match['details'] ?? [];
            $project->match_data = $match;
            return $project;
        });

        $topRecommendations = !empty($talent->assessment_answers)
            ? $projects->sortByDesc('match_score')->take(6)->values()
            : $projects->take(6)->values();

        $appliedProjectIds = Application::where('user_id', $user->id)->pluck('project_id')->toArray();
        $totalApplied = count($appliedProjectIds);

        $recentApplications = Application::with('project')
            ->where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        return view('talent.beranda', [
            'user' => $user,
            'talent' => $talent,
            'topRecommendations' => $topRecommendations,
            'recentApplications' => $recentApplications,
            'appliedProjectIds' => $appliedProjectIds,
            'totalApplied' => $totalApplied,
        ]);
    }

    public function rekomendasi(): View
    {
        $user = Auth::user();
        $talent = $this->getOrCreateTalentProfile($user);

        $projects = Project::openForTalent()
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

        $appliedProjectIds = Application::where('user_id', $user->id)->pluck('project_id')->toArray();

        return view('talent.rekomendasi', [
            'user' => $user,
            'talent' => $talent,
            'projects' => $projects,
            'appliedProjectIds' => $appliedProjectIds,
        ]);
    }

    public function cariProyek(Request $request): View
    {
        $user = Auth::user();
        $talent = $this->getOrCreateTalentProfile($user);

        $query = Project::openForTalent();

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

        $appliedProjectIds = Application::where('user_id', $user->id)->pluck('project_id')->toArray();

        $categories = Project::distinct()->pluck('category')->filter()->values();
        if ($categories->isEmpty()) {
            $categories = collect(['Kreatif', 'Operasional', 'Administrasi', 'Web Development', 'Desain Grafis', 'Digital Marketing']);
        }

        return view('talent.cari', [
            'user' => $user,
            'talent' => $talent,
            'projects' => $projects,
            'categories' => $categories,
            'appliedProjectIds' => $appliedProjectIds,
            'filters' => $request->only(['q', 'category', 'work_type', 'location']),
        ]);
    }

    public function lamarProyek(Request $request, $id): RedirectResponse
    {
        $user = Auth::user();
        $talent = $this->getOrCreateTalentProfile($user);

        if ($talent->verification_status !== 'verified' || $talent->eligibility_status !== 'eligible') {
            return redirect()->route('talent.verifikasi')->with(
                'error',
                'Anda harus menyelesaikan verifikasi status talenta muda terlebih dahulu sebelum dapat melamar proyek.'
            );
        }

        $project = Project::findOrFail($id);

        if (! Project::openForTalent()->whereKey($project->id)->exists()) {
            return redirect()->back()->with(
                'warning',
                'Maaf, proyek ini sudah tidak menerima lamaran karena kuota talenta sudah terpenuhi atau proyek telah ditutup.'
            );
        }

        $existing = Application::where('user_id', $user->id)->where('project_id', $project->id)->first();
        if ($existing) {
            return redirect()->back()->with('warning', 'Anda sudah pernah melamar untuk proyek ini.');
        }

        $message = $request->input('message', 'Saya siap berkontribusi pada proyek ini.');

        Application::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'message' => $message,
            'status' => 'Menunggu',
        ]);

        $matchScore = null;
        if (!empty($talent->assessment_answers)) {
            $match = $this->matchingService->calculateMatch($talent, $project);
            $matchScore = $match['score'] ?? null;
        }

        ProjectApplication::updateOrCreate(
            [
                'project_id' => $project->id,
                'user_id' => $user->id,
            ],
            [
                'status' => ProjectApplication::STATUS_MENUNGGU,
                'cover_letter' => $message,
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
            ]
        );

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

        $project->increment('applicants_count');

        return redirect()->route('talent.lamaran')->with('success', 'Lamaran Anda berhasil dikirim ke penyedia proyek!');
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

        return view('talent.lamaran', [
            'user' => $user,
            'talent' => $talent,
            'applications' => $applications,
            'currentTab' => $currentTab,
        ]);
    }

    public function profil(): View
    {
        $user = Auth::user();
        $talent = $this->getOrCreateTalentProfile($user);

        return view('talent.profil', [
            'user' => $user,
            'talent' => $talent,
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
