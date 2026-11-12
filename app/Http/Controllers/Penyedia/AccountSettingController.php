<?php

namespace App\Http\Controllers\Penyedia;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AccountSettingController extends Controller
{
    /**
     * Show account settings form.
     */
    public function edit(Request $request): View
    {
        return view('penyedia.settings', [
            'user' => $request->user(),
            'profile' => $request->user()->organizationProfile,
        ]);
    }

    /**
     * Update user password securely.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ], [
            'current_password.required' => 'Kata sandi lama wajib diisi.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
            'password.different' => 'Kata sandi baru harus berbeda dari kata sandi saat ini.',
        ]);

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Kata sandi lama yang Anda masukkan tidak sesuai.']);
        }

        $user->update([
            'password' => $request->password,
        ]);

        return back()->with('success', 'Kata sandi berhasil diperbarui.');
    }
}
