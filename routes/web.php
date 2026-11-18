<?php

use App\Http\Controllers\Admin\ProjectController as AdminProjectController;
use App\Http\Controllers\Admin\PromotionController as AdminPromotionController;
use App\Http\Controllers\Admin\RatingModerationController;
use App\Http\Controllers\EngagementController;
use App\Http\Controllers\PromotionTrackingController;
use App\Http\Controllers\ReputationController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VerificationController as AdminVerificationController;
use App\Http\Controllers\OrganizationProfileController;
use App\Http\Controllers\Penyedia\AccountSettingController;
use App\Http\Controllers\Penyedia\ApplicantController;
use App\Http\Controllers\Penyedia\DashboardController;
use App\Http\Controllers\Penyedia\NotificationController;
use App\Http\Controllers\Penyedia\ProjectController;
use App\Http\Controllers\Penyedia\PromotionController as PenyediaPromotionController;
use App\Http\Controllers\TalentController;
use App\Models\Project;
use App\Models\TalentProfile;
use App\Services\WorkstyleMatchingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ================= Landing Page =================
Route::get('/', function () {
    $projects = Project::openForTalent()
        ->orderBy('id', 'asc')
        ->take(6)
        ->get();

    if (Auth::check() && (Auth::user()->isTalenta() || Auth::user()->role === 'talenta_muda' || Auth::user()->role === 'talent')) {
        $talent = TalentProfile::where('user_id', Auth::id())->first();
        if ($talent && !empty($talent->assessment_answers)) {
            $matcher = app(WorkstyleMatchingService::class);
            $projects->transform(function ($project) use ($matcher, $talent) {
                $match = $matcher->calculateMatch($talent, $project);
                $project->match_score = $match['score'];
                return $project;
            });
        }
    }

    return view('landing', compact('projects'));
})->name('landing');

// ================= Authentication Pages =================
Route::get('/login', function () {
    if (Auth::check()) {
        $user = Auth::user();
        if ($user->isAdmin()) {
            return redirect()->route('admin.verifikasi.index');
        }
        if ($user->isPenyedia()) {
            return redirect()->route('penyedia.dashboard');
        }
        return redirect()->route('talent.beranda');
    }
    return view('login');
})->name('login');

Route::get('/register', function () {
    if (Auth::check()) {
        $user = Auth::user();
        if ($user->isAdmin()) {
            return redirect()->route('admin.verifikasi.index');
        }
        if ($user->isPenyedia()) {
            return redirect()->route('penyedia.dashboard');
        }
        return redirect()->route('talent.beranda');
    }
    return view('register');
})->name('register');

Route::post('/web-login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
    ]);

    if (! Auth::attempt($credentials, $request->boolean('remember'))) {
        return response()->json([
            'message' => 'Email atau kata sandi salah.',
        ], 422);
    }

    $request->session()->regenerate();
    $user = Auth::user();

    // Check if user is inactive
    if (isset($user->is_active) && ! $user->is_active) {
        Auth::logout();
        $supportEmail = config('app.support_email');
        return response()->json([
            'message' => "Akun Anda dinonaktifkan oleh Administrator. Untuk informasi lebih lanjut atau pengajuan aktivasi kembali, hubungi kami di {$supportEmail}.",
            'reason' => 'account_inactive',
            'support_email' => $supportEmail,
        ], 403);
    }

    // Role-based redirects
    if ($user->isAdmin()) {
        return response()->json([
            'message' => 'Login berhasil.',
            'redirect' => route('admin.verifikasi.index'),
        ]);
    }

    if ($user->isPenyedia()) {
        return response()->json([
            'message' => 'Login berhasil.',
            'redirect' => route('penyedia.dashboard'),
        ]);
    }

    return response()->json([
        'message' => 'Login berhasil.',
        'redirect' => route('talent.beranda'),
    ]);
});

Route::middleware('auth')->get('/dashboard', function () {
    $user = Auth::user();
    if ($user->isAdmin()) {
        return redirect()->route('admin.verifikasi.index');
    }
    if ($user->isPenyedia()) {
        return redirect()->route('penyedia.dashboard');
    }
    return redirect()->route('talent.beranda');
})->name('dashboard');

// ================= Provider (Penyedia Proyek) Routes =================
Route::middleware(['auth', 'penyedia'])->prefix('penyedia')->name('penyedia.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Projects CRUD
    Route::get('/proyek', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/proyek/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/proyek', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/proyek/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::put('/proyek/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::patch('/proyek/{project}/status', [ProjectController::class, 'updateStatus'])->name('projects.status');
    Route::delete('/proyek/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

    // Applicants Management
    Route::get('/pelamar', [ApplicantController::class, 'index'])->name('applicants.index');
    Route::patch('/pelamar/{application}/status', [ApplicantController::class, 'updateStatus'])->name('applicants.status');

    // Business Profile
    Route::get('/profil', [OrganizationProfileController::class, 'edit'])->name('profile');
    Route::post('/profil', [OrganizationProfileController::class, 'update'])->name('profile.update');

    // Notifications
    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifikasi/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');
    Route::patch('/notifikasi/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');

    // Account Settings
    Route::get('/pengaturan', [AccountSettingController::class, 'edit'])->name('settings');
    Route::post('/pengaturan/password', [AccountSettingController::class, 'updatePassword'])->name('settings.password');

    // Sponsored Project (Promosi Proyek)
    Route::prefix('promosi')->name('promotions.')->group(function () {
        Route::get('/', [PenyediaPromotionController::class, 'index'])->name('index');
        Route::get('/riwayat', [PenyediaPromotionController::class, 'history'])->name('history');
        Route::get('/proyek/{project}/checkout', [PenyediaPromotionController::class, 'checkout'])->name('checkout');
        Route::post('/proyek/{project}/checkout', [PenyediaPromotionController::class, 'store'])->name('store');
        Route::get('/{promotion}/pembayaran', [PenyediaPromotionController::class, 'payment'])->name('payment');
        Route::get('/{promotion}/status', [PenyediaPromotionController::class, 'status'])->name('status');
        Route::post('/{promotion}/konfirmasi', [PenyediaPromotionController::class, 'submitProof'])->name('proof.submit');
        Route::get('/{promotion}/bukti', [PenyediaPromotionController::class, 'proofFile'])->name('proof');
        Route::post('/{promotion}/simulasi/berhasil', [PenyediaPromotionController::class, 'simulatePaid'])->name('simulate.paid');
        Route::post('/{promotion}/simulasi/gagal', [PenyediaPromotionController::class, 'simulateFailed'])->name('simulate.failed');
        Route::post('/{promotion}/batal', [PenyediaPromotionController::class, 'cancel'])->name('cancel');
        Route::get('/{promotion}/statistik', [PenyediaPromotionController::class, 'stats'])->name('stats');
    });

    // Penilaian Talenta (riwayat pengerjaan & rating)
    Route::get('/penilaian', [EngagementController::class, 'index'])->name('engagements.index');
    Route::post('/penilaian/{completion}/ajukan-selesai', [EngagementController::class, 'requestCompletion'])->name('engagements.request');
    Route::post('/penilaian/{completion}/konfirmasi', [EngagementController::class, 'confirm'])->name('engagements.confirm');
    Route::post('/penilaian/{completion}/sengketa', [EngagementController::class, 'dispute'])->name('engagements.dispute');
    Route::get('/penilaian/{completion}/nilai', [EngagementController::class, 'rateForm'])->name('engagements.rate');
    Route::post('/penilaian/{completion}/nilai', [EngagementController::class, 'rateStore'])->name('engagements.rate.store');
    Route::post('/rating/{rating}/laporkan', [EngagementController::class, 'report'])->name('ratings.report');
    Route::get('/talenta/{user}', [ReputationController::class, 'talent'])->name('talents.show');
});

// ================= Talenta Muda Routes =================
Route::middleware(['auth'])->prefix('talent')->name('talent.')->group(function () {
    Route::get('/', [TalentController::class, 'beranda'])->name('beranda');
    Route::get('/beranda', [TalentController::class, 'beranda']);

    // Rekomendasi & Cari Proyek
    Route::get('/rekomendasi', [TalentController::class, 'rekomendasi'])->name('rekomendasi');
    Route::get('/proyek', [TalentController::class, 'cariProyek'])->name('cari');
    Route::post('/proyek/{id}/lamar', [TalentController::class, 'lamarProyek'])->name('lamar');
    Route::get('/proyek/{id}/status-lamaran', [TalentController::class, 'statusLamaran'])->name('lamar.status');
    Route::patch('/proyek/{id}/batal', [TalentController::class, 'batalkanLamaran'])->name('lamar.batal');
    Route::get('/lamaran', [TalentController::class, 'lamaranSaya'])->name('lamaran');

    // Profil & Portofolio
    Route::get('/profil', [TalentController::class, 'profil'])->name('profil');
    Route::post('/profil', [TalentController::class, 'updateProfil'])->name('profil.update');
    Route::post('/profil/portofolio', [TalentController::class, 'tambahPortofolio'])->name('profil.tambah-portofolio');
    Route::put('/profil/portofolio/{id}', [TalentController::class, 'ubahPortofolio'])->name('profil.ubah-portofolio');
    Route::delete('/profil/portofolio/{id}', [TalentController::class, 'hapusPortofolio'])->name('profil.hapus-portofolio');

    // Verifikasi Dokumen PDF & Screening
    Route::get('/verifikasi', [TalentController::class, 'verifikasi'])->name('verifikasi');
    Route::post('/verifikasi', [TalentController::class, 'ajukanVerifikasi'])->name('verifikasi.ajukan');
    Route::get('/dokumen', [TalentController::class, 'previewDokumen'])->name('dokumen');

    // Asesmen Gaya Kerja
    Route::get('/asesmen', [TalentController::class, 'asesmen'])->name('asesmen');
    Route::post('/asesmen', [TalentController::class, 'simpanAsesmen'])->name('asesmen.simpan');

    // Riwayat Proyek & penilaian Penyedia
    Route::get('/riwayat', [EngagementController::class, 'index'])->name('riwayat');
    Route::post('/riwayat/{completion}/ajukan-selesai', [EngagementController::class, 'requestCompletion'])->name('riwayat.request');
    Route::post('/riwayat/{completion}/konfirmasi', [EngagementController::class, 'confirm'])->name('riwayat.confirm');
    Route::post('/riwayat/{completion}/sengketa', [EngagementController::class, 'dispute'])->name('riwayat.dispute');
    Route::get('/riwayat/{completion}/nilai', [EngagementController::class, 'rateForm'])->name('riwayat.rate');
    Route::post('/riwayat/{completion}/nilai', [EngagementController::class, 'rateStore'])->name('riwayat.rate.store');
    Route::post('/rating/{rating}/laporkan', [EngagementController::class, 'report'])->name('ratings.report');
    Route::get('/penyedia/{user}', [ReputationController::class, 'provider'])->name('providers.show');

    // Tracking Sponsored Project (dibatasi laju untuk meredam spam)
    Route::post('/promosi/{promotion}/impression', [PromotionTrackingController::class, 'impression'])
        ->middleware('throttle:120,1')->name('promo.impression');
    Route::post('/promosi/{promotion}/click', [PromotionTrackingController::class, 'click'])
        ->middleware('throttle:60,1')->name('promo.click');
});

// ================= Admin Panel Routes =================
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Verifikasi Talenta
    Route::get('/verifikasi', [AdminVerificationController::class, 'index'])->name('verifikasi.index');
    Route::get('/verifikasi/{id}/dokumen', [AdminVerificationController::class, 'previewDokumen'])->name('verifikasi.dokumen');
    Route::post('/verifikasi/{id}/approve', [AdminVerificationController::class, 'approve'])->name('verifikasi.approve');
    Route::post('/verifikasi/{id}/reject', [AdminVerificationController::class, 'reject'])->name('verifikasi.reject');

    // Manajemen Pengguna
    Route::get('/pengguna', [AdminUserController::class, 'index'])->name('pengguna.index');
    Route::post('/pengguna/{id}/update', [AdminUserController::class, 'update'])->name('pengguna.update');
    Route::post('/pengguna/{id}/toggle', [AdminUserController::class, 'toggleActive'])->name('pengguna.toggle');

    // Manajemen Proyek
    Route::get('/proyek', [AdminProjectController::class, 'index'])->name('proyek.index');
    Route::post('/proyek/{id}/status', [AdminProjectController::class, 'updateStatus'])->name('proyek.status');

    // Manajemen Promosi (tidak ada endpoint untuk mengubah status pembayaran)
    Route::prefix('promosi')->name('promotions.')->group(function () {
        Route::get('/', [AdminPromotionController::class, 'index'])->name('index');
        Route::get('/{promotion}', [AdminPromotionController::class, 'show'])->name('show');
        Route::post('/{promotion}/stop', [AdminPromotionController::class, 'stop'])->name('stop');
    });

    // Verifikasi Pembayaran QRIS (manual)
    Route::prefix('pembayaran')->name('payments.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\PaymentVerificationController::class, 'index'])->name('index');
        Route::get('/{transaction}', [\App\Http\Controllers\Admin\PaymentVerificationController::class, 'show'])->name('show');
        Route::get('/{transaction}/bukti', [\App\Http\Controllers\Admin\PaymentVerificationController::class, 'proof'])->name('proof');
        Route::post('/{transaction}/setujui', [\App\Http\Controllers\Admin\PaymentVerificationController::class, 'approve'])->name('approve');
        Route::post('/{transaction}/tolak', [\App\Http\Controllers\Admin\PaymentVerificationController::class, 'reject'])->name('reject');
    });

    // Moderasi Rating
    Route::prefix('rating')->name('ratings.')->group(function () {
        Route::get('/', [RatingModerationController::class, 'index'])->name('index');
        Route::post('/{rating}/sembunyikan', [RatingModerationController::class, 'hide'])->name('hide');
        Route::post('/{rating}/pulihkan', [RatingModerationController::class, 'restore'])->name('restore');
        Route::post('/{rating}/batalkan', [RatingModerationController::class, 'void'])->name('void');
        Route::post('/{rating}/batal-pembatalan', [RatingModerationController::class, 'unvoid'])->name('unvoid');
        Route::post('/laporan/{report}', [RatingModerationController::class, 'handleReport'])->name('reports.handle');
        Route::post('/sengketa/{completion}', [RatingModerationController::class, 'resolveDispute'])->name('disputes.resolve');
    });
});

// ================= Gambar Sampul Proyek =================
// Dilayani lewat route agar tidak bergantung pada symlink public/storage (Windows-friendly).
Route::get('/project-cover/{project}', function (Project $project) {
    $path = $project->cover_image;
    abort_unless($path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path), 404);

    return \Illuminate\Support\Facades\Storage::disk('public')->response($path, null, [
        'Cache-Control' => 'public, max-age=604800',
    ]);
})->name('project.cover');

// ================= Logout =================
Route::post('/web-logout', function (Request $request) {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/login')->with('success', 'Anda telah berhasil keluar.');
})->middleware('auth')->name('logout');

Route::get('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
});
