@extends('layouts.penyedia')

@section('title', $isEdit ? 'Edit Proyek' : 'Buat Proyek')

@section('content')
<div class="page-header">
    <div class="page-title-box">
        <h1>{{ $isEdit ? 'Edit Proyek' : 'Buat Proyek' }}</h1>
        <p>Proyek draf tidak tampil pada pencarian publik.</p>
    </div>
    <div>
        <a href="{{ route('penyedia.projects.index') }}" class="btn-secondary-action">
            &larr; Kembali ke Daftar
        </a>
    </div>
</div>

@if($errors->any())
    <div class="alert-box alert-error" style="display: block;">
        <div style="font-weight: 700; margin-bottom: 6px;">Terdapat kesalahan pada isian form:</div>
        <ul style="margin: 0; padding-left: 20px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $isEdit ? route('penyedia.projects.update', $project) : route('penyedia.projects.store') }}">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <!-- BAGIAN 1: INFORMASI DASAR -->
    <div style="background: #ffffff; border: 1px solid #e9edf2; border-radius: 14px; padding: 28px; margin-bottom: 24px; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid #f3f4f6;">
            <div style="width: 28px; height: 28px; border-radius: 50%; background: #239b65; color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">1</div>
            <div>
                <h2 style="font-size: 17px; font-weight: 700; color: #111827; margin: 0 0 2px;">Informasi Dasar</h2>
                <p style="margin: 0; font-size: 13px; color: #6b7280;">Identitas utama proyek yang akan dilihat talenta.</p>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div style="grid-column: 1 / -1;">
                <label style="display: block; font-size: 13.5px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                    Judul Proyek <span style="color: #dc2626;">*</span>
                </label>
                <input type="text" name="title" value="{{ old('title', $project->title) }}" placeholder="Contoh: Fotografer Acara Kuliner Lokal" required
                       style="width: 100%; padding: 10px 14px; border: 1.5px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none;">
            </div>

            <div>
                <label style="display: block; font-size: 13.5px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                    Kategori <span style="color: #dc2626;">*</span>
                </label>
                <input type="text" name="category" value="{{ old('category', $project->category) }}" placeholder="Contoh: Kreatif, Desain, IT, Kuliner" required
                       style="width: 100%; padding: 10px 14px; border: 1.5px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none;">
            </div>

            <div>
                <label style="display: block; font-size: 13.5px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                    Lokasi <span style="color: #dc2626;">*</span>
                </label>
                <input type="text" name="location" value="{{ old('location', $project->location) }}" placeholder="Contoh: Bandung / Jakarta Selatan" required
                       style="width: 100%; padding: 10px 14px; border: 1.5px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none;">
            </div>

            <div>
                <label style="display: block; font-size: 13.5px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                    Kompensasi
                </label>
                <input type="text" name="compensation" value="{{ old('compensation', $project->compensation) }}" placeholder="Contoh: Rp 750.000 + Transport"
                       style="width: 100%; padding: 10px 14px; border: 1.5px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none;">
            </div>

            <div>
                <label style="display: block; font-size: 13.5px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                    Metode Kerja <span style="color: #dc2626;">*</span>
                </label>
                <select name="work_method" required style="width: 100%; padding: 10px 14px; border: 1.5px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none; background: #ffffff;">
                    <option value="onsite" {{ old('work_method', $project->work_method) === 'onsite' ? 'selected' : '' }}>Onsite (Di Lokasi)</option>
                    <option value="hybrid" {{ old('work_method', $project->work_method) === 'hybrid' ? 'selected' : '' }}>Hybrid</option>
                    <option value="remote" {{ old('work_method', $project->work_method) === 'remote' ? 'selected' : '' }}>Remote (Daring)</option>
                </select>
            </div>

            <div>
                <label style="display: block; font-size: 13.5px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                    Kompetensi yang Dibutuhkan (Pisahkan koma)
                </label>
                <input type="text" name="required_competencies" value="{{ old('required_competencies', $project->required_competencies) }}" placeholder="Contoh: Fotografi, Adobe Lightroom, Komunikasi"
                       style="width: 100%; padding: 10px 14px; border: 1.5px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none;">
            </div>

            <div>
                <label style="display: block; font-size: 13.5px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                    Ketersediaan / Jadwal <span style="color: #dc2626;">*</span>
                </label>
                <input type="text" name="availability" value="{{ old('availability', $project->availability) }}" placeholder="Contoh: Akhir pekan, Fleksibel, Part-time" required
                       style="width: 100%; padding: 10px 14px; border: 1.5px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none;">
            </div>

            <div>
                <label style="display: block; font-size: 13.5px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                    Jumlah Talenta Dibutuhkan <span style="color: #dc2626;">*</span>
                </label>
                <input type="number" name="talents_needed" min="1" max="99" value="{{ old('talents_needed', $project->talents_needed ?: 1) }}" required
                       style="width: 100%; padding: 10px 14px; border: 1.5px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none;">
            </div>
        </div>
    </div>

    <!-- BAGIAN 2: JADWAL & DETAIL PEKERJAAN -->
    <div style="background: #ffffff; border: 1px solid #e9edf2; border-radius: 14px; padding: 28px; margin-bottom: 24px; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid #f3f4f6;">
            <div style="width: 28px; height: 28px; border-radius: 50%; background: #239b65; color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">2</div>
            <div>
                <h2 style="font-size: 17px; font-weight: 700; color: #111827; margin: 0 0 2px;">Jadwal dan Detail Pekerjaan</h2>
                <p style="margin: 0; font-size: 13px; color: #6b7280;">Tentukan periode, tenggat, dan ruang lingkup pekerjaan.</p>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div>
                <label style="display: block; font-size: 13.5px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                    Tanggal Mulai <span style="color: #dc2626;">*</span>
                </label>
                <input type="date" name="start_date" value="{{ old('start_date', $project->start_date?->format('Y-m-d')) }}" required
                       style="width: 100%; padding: 10px 14px; border: 1.5px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none;">
            </div>

            <div>
                <label style="display: block; font-size: 13.5px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                    Tanggal Selesai <span style="color: #dc2626;">*</span>
                </label>
                <input type="date" name="end_date" value="{{ old('end_date', $project->end_date?->format('Y-m-d')) }}" required
                       style="width: 100%; padding: 10px 14px; border: 1.5px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none;">
            </div>

            <div>
                <label style="display: block; font-size: 13.5px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                    Batas Pendaftaran
                </label>
                <input type="date" name="registration_deadline" value="{{ old('registration_deadline', $project->registration_deadline?->format('Y-m-d')) }}"
                       style="width: 100%; padding: 10px 14px; border: 1.5px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none;">
            </div>
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; font-size: 13.5px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                Deskripsi Proyek <span style="color: #dc2626;">*</span>
            </label>
            <textarea name="description" rows="4" placeholder="Jelaskan tujuan dan ruang lingkup proyek secara jelas..." required
                      style="width: 100%; padding: 12px 14px; border: 1.5px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none; font-family: inherit;">{{ old('description', $project->description) }}</textarea>
        </div>

        <div>
            <label style="display: block; font-size: 13.5px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                Tanggung Jawab Pekerjaan
            </label>
            <textarea name="responsibilities" rows="3" placeholder="Sebutkan tanggung jawab utama talenta..."
                      style="width: 100%; padding: 12px 14px; border: 1.5px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none; font-family: inherit;">{{ old('responsibilities', $project->responsibilities) }}</textarea>
        </div>
    </div>

    <!-- BAGIAN 3: KARAKTERISTIK GAYA KERJA (8 DIMENSI) -->
    <div style="background: #ffffff; border: 1px solid #e9edf2; border-radius: 14px; padding: 28px; margin-bottom: 28px; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px; padding-bottom: 14px; border-bottom: 1px solid #f3f4f6;">
            <div style="width: 28px; height: 28px; border-radius: 50%; background: #239b65; color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">3</div>
            <div>
                <h2 style="font-size: 17px; font-weight: 700; color: #111827; margin: 0 0 2px;">Karakteristik Gaya Kerja</h2>
                <p style="margin: 0; font-size: 13px; color: #6b7280;">
                    Pilihan ini membantu TANDANG merekomendasikan proyek kepada talenta dengan preferensi yang serupa. Kosongkan dimensi yang belum ingin ditentukan.
                </p>
            </div>
        </div>

        @php
            $dimensions = [
                'independence_teamwork' => ['left' => 'Mandiri', 'right' => 'Kerja tim'],
                'structure_flexibility' => ['left' => 'Terstruktur', 'right' => 'Fleksibel'],
                'written_verbal' => ['left' => 'Tertulis', 'right' => 'Langsung'],
                'remote_field' => ['left' => 'Remote', 'right' => 'Lapangan'],
                'analytical_practical' => ['left' => 'Analitis', 'right' => 'Praktis'],
                'stable_dynamic' => ['left' => 'Stabil', 'right' => 'Dinamis'],
                'limited_intense_interaction' => ['left' => 'Interaksi terbatas', 'right' => 'Interaksi intens'],
                'detailed_spontaneous_planning' => ['left' => 'Rencana rinci', 'right' => 'Spontan'],
            ];
            $savedCharacteristics = $project->workstyle_characteristics ?? [];
        @endphp

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            @foreach($dimensions as $key => $dim)
                @php
                    $currentVal = old("workstyle_characteristics.{$key}", $savedCharacteristics[$key] ?? '');
                @endphp
                <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px;">
                    <div style="font-size: 13.5px; font-weight: 700; color: #1f2a37; margin-bottom: 8px;">
                        {{ $dim['left'] }} &mdash; {{ $dim['right'] }}
                    </div>
                    <select name="workstyle_characteristics[{{ $key }}]"
                            style="width: 100%; padding: 8px 12px; border: 1.5px solid #d1d5db; border-radius: 6px; font-size: 13px; background: #ffffff; outline: none;">
                        <option value="">Belum ditentukan</option>
                        <option value="{{ $dim['left'] }}" {{ $currentVal === $dim['left'] ? 'selected' : '' }}>
                            {{ $dim['left'] }}
                        </option>
                        <option value="{{ $dim['right'] }}" {{ $currentVal === $dim['right'] ? 'selected' : '' }}>
                            {{ $dim['right'] }}
                        </option>
                    </select>
                </div>
            @endforeach
        </div>
    </div>

    <!-- FORM ACTIONS -->
    <div style="display: flex; align-items: center; justify-content: flex-end; gap: 12px; padding: 16px 0;">
        <a href="{{ route('penyedia.projects.index') }}" class="btn-secondary-action">
            Batal
        </a>

        <button type="submit" name="action" value="draft" class="btn-secondary-action" style="background: #ffffff; color: #374151; font-weight: 600;">
            Simpan Draft
        </button>

        <button type="submit" name="action" value="published" class="btn-primary-action">
            Publikasikan
        </button>
    </div>
</form>
@endsection
