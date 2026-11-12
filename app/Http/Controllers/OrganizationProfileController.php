<?php

namespace App\Http\Controllers;

use App\Models\OrganizationProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class OrganizationProfileController extends Controller
{
    /**
     * Show the form for editing the organization profile.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        $profile = $user->organizationProfile()->firstOrCreate(
            ['user_id' => $user->id],
            ['organization_name' => $user->name]
        );

        return view('penyedia.profile', [
            'user' => $user,
            'profile' => $profile,
        ]);
    }

    /**
     * Update the organization profile in storage.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'organization_name' => ['required', 'string', 'max:255'],
            'industry_type'     => ['nullable', 'string', 'max:100'],
            'description'       => ['nullable', 'string', 'max:3000'],
            'address'           => ['nullable', 'string', 'max:500'],
            'city'              => ['nullable', 'string', 'max:100'],
            'phone'             => ['nullable', 'string', 'max:30'],
            'website'           => ['nullable', 'url', 'max:255'],
            'logo'              => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
        ], [
            'organization_name.required' => 'Nama organisasi wajib diisi.',
            'website.url'                => 'Format URL website tidak valid. Sertakan http:// atau https://',
            'logo.image'                 => 'Berkas logo harus berupa gambar.',
            'logo.mimes'                 => 'Format logo harus jpeg, png, jpg, webp, atau svg.',
            'logo.max'                   => 'Ukuran logo maksimal 2MB.',
        ]);

        $profile = $user->organizationProfile()->firstOrCreate(
            ['user_id' => $user->id],
            ['organization_name' => $user->name]
        );

        $logoPath = $profile->logo;

        if ($request->hasFile('logo')) {
            $uploadDirectory = public_path('uploads/logos');
            if (! File::isDirectory($uploadDirectory)) {
                File::makeDirectory($uploadDirectory, 0755, true, true);
            }

            // Remove old logo if exists
            if ($profile->logo && File::exists(public_path($profile->logo))) {
                File::delete(public_path($profile->logo));
            }

            $file = $request->file('logo');
            $filename = 'logo_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move($uploadDirectory, $filename);
            $logoPath = 'uploads/logos/' . $filename;
        }

        $profile->update([
            'organization_name' => $validated['organization_name'],
            'industry_type'     => $validated['industry_type'] ?? null,
            'description'       => $validated['description'] ?? null,
            'address'           => $validated['address'] ?? null,
            'city'              => $validated['city'] ?? null,
            'phone'             => $validated['phone'] ?? null,
            'website'           => $validated['website'] ?? null,
            'logo'              => $logoPath,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Profil organisasi berhasil diperbarui.',
                'profile' => $profile,
            ]);
        }

        return redirect()->route('penyedia.profile')
            ->with('success', 'Profil organisasi berhasil diperbarui.');
    }
}
