<?php

use App\Http\Controllers\OrganizationProfileController;
use App\Http\Controllers\Penyedia\AccountSettingController;
use App\Http\Controllers\Penyedia\ApplicantController;
use App\Http\Controllers\Penyedia\DashboardController;
use App\Http\Controllers\Penyedia\NotificationController;
use App\Http\Controllers\Penyedia\ProjectController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
});

Route::get('/login', function () {
    if (Auth::check()) {
        return Auth::user()->isPenyedia()
            ? redirect()->route('penyedia.dashboard')
            : redirect()->route('dashboard');
    }
    return view('login');
})->name('login');

Route::get('/register', function () {
    if (Auth::check()) {
        return Auth::user()->isPenyedia()
            ? redirect()->route('penyedia.dashboard')
            : redirect()->route('dashboard');
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
    $redirect = $user->isPenyedia()
        ? route('penyedia.dashboard')
        : route('dashboard');

    return response()->json([
        'message' => 'Login berhasil.',
        'redirect' => $redirect,
    ]);
});

Route::middleware('auth')->get('/dashboard', function () {
    // If Penyedia user accesses /dashboard, redirect to provider dashboard
    if (Auth::user()->isPenyedia()) {
        return redirect()->route('penyedia.dashboard');
    }

    return view('dashboard', [
        'user' => Auth::user(),
    ]);
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
});

Route::post('/web-logout', function (Request $request) {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/login');
})->middleware('auth')->name('logout');
