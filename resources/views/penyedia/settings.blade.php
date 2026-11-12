@extends('layouts.penyedia')

@section('title', 'Pengaturan Akun')

@section('content')
<div class="page-header">
    <div class="page-title-box">
        <h1>Pengaturan Akun</h1>
        <p>Kelola kata sandi untuk akunmu.</p>
    </div>
</div>

@if($errors->any())
    <div class="alert-box alert-error" style="display: block;">
        <div style="font-weight: 700; margin-bottom: 6px;">Gagal memperbarui kata sandi:</div>
        <ul style="margin: 0; padding-left: 20px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div style="max-width: 580px; background: #ffffff; border: 1px solid #e9edf2; border-radius: 14px; padding: 28px; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
    <form action="{{ route('penyedia.settings.password') }}" method="POST">
        @csrf

        <div style="margin-bottom: 20px;">
            <label style="display: block; font-size: 13.5px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                Kata sandi lama <span style="color: #dc2626;">*</span>
            </label>
            <input type="password" name="current_password" required placeholder="Masukkan kata sandi saat ini"
                   style="width: 100%; padding: 10px 14px; border: 1.5px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none;">
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; font-size: 13.5px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                Kata sandi baru <span style="color: #dc2626;">*</span>
            </label>
            <input type="password" name="password" required minlength="8" placeholder="Minimal 8 karakter"
                   style="width: 100%; padding: 10px 14px; border: 1.5px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none;">
        </div>

        <div style="margin-bottom: 24px;">
            <label style="display: block; font-size: 13.5px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                Konfirmasi kata sandi baru <span style="color: #dc2626;">*</span>
            </label>
            <input type="password" name="password_confirmation" required minlength="8" placeholder="Ulangi kata sandi baru"
                   style="width: 100%; padding: 10px 14px; border: 1.5px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none;">
        </div>

        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 12px;">
            <button type="submit" class="btn-primary-action" style="padding: 10px 20px;">
                Ubah Kata Sandi
            </button>
        </div>
    </form>
</div>
@endsection
