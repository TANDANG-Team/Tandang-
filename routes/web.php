<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
});

Route::view('/login', 'login')->name('login');
Route::view('/register', 'register')->name('register');

// Web Authentication Endpoint
Route::post('/web-login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
    ]);

    if (! Auth::attempt($credentials, $request->boolean('remember'))) {
        return response()->json([
            'message' => 'Email atau kata sandi tidak sesuai.',
        ], 422);
    }

    $request->session()->regenerate();
    $user = Auth::user();

    // Check if user is admin or uses special domain @tandang.id
    if ($user->role === 'admin' || str_ends_with(strtolower($user->email), '@tandang.id')) {
        return response()->json([
            'message' => 'Login Admin berhasil. Mengalihkan ke panel verifikasi...',
            'redirect' => route('admin.verifikasi.index'),
        ]);
    }

    // Default redirect for regular users
    return response()->json([
        'message' => 'Login berhasil. Mengalihkan...',
        'redirect' => url('/'),
    ]);
});

Route::post('/web-logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login')->with('success', 'Anda telah berhasil keluar.');
})->middleware('auth')->name('logout');

// Admin Panel Routes (Protected by Auth & Admin Middleware)
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Verifikasi Talenta
    Route::get('/verifikasi', [App\Http\Controllers\Admin\VerificationController::class, 'index'])->name('verifikasi.index');
    Route::post('/verifikasi/{id}/approve', [App\Http\Controllers\Admin\VerificationController::class, 'approve'])->name('verifikasi.approve');
    Route::post('/verifikasi/{id}/reject', [App\Http\Controllers\Admin\VerificationController::class, 'reject'])->name('verifikasi.reject');

    // Kelola Pengguna
    Route::get('/pengguna', [App\Http\Controllers\Admin\UserController::class, 'index'])->name('pengguna.index');
    Route::post('/pengguna/{id}/update', [App\Http\Controllers\Admin\UserController::class, 'update'])->name('pengguna.update');
    Route::post('/pengguna/{id}/toggle', [App\Http\Controllers\Admin\UserController::class, 'toggleActive'])->name('pengguna.toggle');

    // Kelola Proyek
    Route::get('/proyek', [App\Http\Controllers\Admin\ProjectController::class, 'index'])->name('proyek.index');
    Route::post('/proyek/{id}/status', [App\Http\Controllers\Admin\ProjectController::class, 'updateStatus'])->name('proyek.status');
});

