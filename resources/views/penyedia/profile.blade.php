@extends('layouts.penyedia')

@section('title', 'Profil Usaha')

@section('content')
<div class="page-header">
    <div class="page-title-box">
        <h1>Profil Usaha</h1>
        <p>Kelola Profil Organisasi &bull; Nama usaha ini tampil di seluruh kartu proyek.</p>
    </div>
</div>

@if($errors->any())
    <div class="alert-box alert-error" style="display: block;">
        <div style="font-weight: 700; margin-bottom: 6px;">Terdapat kesalahan pada input:</div>
        <ul style="margin: 0; padding-left: 20px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div style="display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start;">
    <!-- Form Side -->
    <div style="background: #ffffff; border: 1px solid #e9edf2; border-radius: 14px; padding: 28px; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
        <form action="{{ route('penyedia.profile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div style="grid-column: 1 / -1;">
                    <label style="display: block; font-size: 13.5px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                        Nama Usaha <span style="color: #dc2626;">*</span>
                    </label>
                    <input type="text" name="organization_name" id="orgNameInput" value="{{ old('organization_name', $profile->organization_name) }}" required
                           style="width: 100%; padding: 10px 14px; border: 1.5px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none;">
                </div>

                <div>
                    <label style="display: block; font-size: 13.5px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                        Penanggung Jawab
                    </label>
                    <input type="text" value="{{ $user->name }}" readonly disabled
                           style="width: 100%; padding: 10px 14px; border: 1.5px solid #e5e7eb; border-radius: 8px; font-size: 14px; background: #f9fafb; color: #6b7280; outline: none;">
                </div>

                <div>
                    <label style="display: block; font-size: 13.5px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                        Email Usaha / Akun
                    </label>
                    <input type="email" value="{{ $user->email }}" readonly disabled
                           style="width: 100%; padding: 10px 14px; border: 1.5px solid #e5e7eb; border-radius: 8px; font-size: 14px; background: #f9fafb; color: #6b7280; outline: none;">
                </div>

                <div>
                    <label style="display: block; font-size: 13.5px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                        Nomor Kontak
                    </label>
                    <input type="text" name="phone" value="{{ old('phone', $profile->phone) }}" placeholder="+62 812..."
                           style="width: 100%; padding: 10px 14px; border: 1.5px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none;">
                </div>

                <div>
                    <label style="display: block; font-size: 13.5px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                        Kota
                    </label>
                    <input type="text" name="city" value="{{ old('city', $profile->city) }}" placeholder="Contoh: Bandung"
                           style="width: 100%; padding: 10px 14px; border: 1.5px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none;">
                </div>

                <div style="grid-column: 1 / -1;">
                    <label style="display: block; font-size: 13.5px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                        Website
                    </label>
                    <input type="url" name="website" value="{{ old('website', $profile->website) }}" placeholder="https://..."
                           style="width: 100%; padding: 10px 14px; border: 1.5px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none;">
                </div>

                <div style="grid-column: 1 / -1;">
                    <label style="display: block; font-size: 13.5px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                        Alamat Lengkap
                    </label>
                    <textarea name="address" rows="2" placeholder="Jl. ..."
                              style="width: 100%; padding: 10px 14px; border: 1.5px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none; font-family: inherit;">{{ old('address', $profile->address) }}</textarea>
                </div>

                <div style="grid-column: 1 / -1;">
                    <label style="display: block; font-size: 13.5px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                        Deskripsi Usaha
                    </label>
                    <textarea name="description" rows="3" placeholder="Ceritakan profil singkat usaha Anda..."
                              style="width: 100%; padding: 10px 14px; border: 1.5px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none; font-family: inherit;">{{ old('description', $profile->description) }}</textarea>
                </div>

                <div style="grid-column: 1 / -1;">
                    <label style="display: block; font-size: 13.5px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                        Logo Usaha
                    </label>
                    <input type="file" name="logo" accept="image/*"
                           style="width: 100%; padding: 8px; border: 1.5px dashed #d1d5db; border-radius: 8px; font-size: 13px; background: #f9fafb;">
                    <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">Format: JPEG, PNG, WEBP, SVG (Maks. 2MB)</div>
                </div>
            </div>

            <div style="text-align: right; padding-top: 10px;">
                <button type="submit" class="btn-primary-action" style="padding: 10px 24px;">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

    <!-- Preview Card Matching Penyedia.pdf -->
    <div style="background: #ffffff; border: 1px solid #e9edf2; border-radius: 14px; padding: 24px; text-align: center; box-shadow: 0 2px 6px rgba(0,0,0,0.02); position: sticky; top: 24px;">
        <div style="font-size: 12px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 16px;">
            Tampilan Kartu Usaha
        </div>

        <div style="width: 80px; height: 80px; border-radius: 18px; background: #e9f7ef; border: 2px solid #239b65; margin: 0 auto 14px; display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 700; color: #239b65; overflow: hidden;">
            @if($profile->logo)
                <img src="{{ asset($profile->logo) }}" alt="Logo" style="width: 100%; height: 100%; object-fit: cover;">
            @else
                {{ strtoupper(substr($profile->organization_name ?: $user->name, 0, 1)) }}
            @endif
        </div>

        <h3 style="font-size: 18px; font-weight: 700; color: #111827; margin: 0 0 4px;">
            {{ $profile->organization_name ?: 'Nama Usaha' }}
        </h3>
        <div style="font-size: 13px; color: #6b7280; margin-bottom: 12px;">
            {{ $profile->city ?: 'Kota belum diisi' }}
        </div>

        <div style="background: #f9fafb; border-radius: 8px; padding: 12px; font-size: 13px; color: #4b5563; line-height: 1.5; text-align: left; margin-bottom: 16px;">
            {{ $profile->description ?: 'Deskripsi usaha akan tampil kepada talenta yang mencari proyek.' }}
        </div>

        <div style="text-align: left; font-size: 12.5px; color: #374151; display: flex; flex-direction: column; gap: 8px; border-top: 1px solid #f3f4f6; padding-top: 14px;">
            <div><strong>Kontak:</strong> {{ $profile->phone ?: '-' }}</div>
            <div><strong>Email:</strong> {{ $user->email }}</div>
            @if($profile->website)
                <div><strong>Web:</strong> <a href="{{ $profile->website }}" target="_blank" style="color: #2563eb; text-decoration: underline;">{{ $profile->website }}</a></div>
            @endif
        </div>
    </div>
</div>
@endsection
