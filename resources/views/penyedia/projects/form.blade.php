@extends('layouts.penyedia')

@section('title', $isEdit ? 'Edit Proyek' : 'Buat Proyek')


@push('styles')
<style>
    .ws-list { display: flex; flex-direction: column; gap: 14px; }
    .ws-row { display: grid; grid-template-columns: minmax(200px, 1fr) 2fr; gap: 14px; align-items: center; padding: 14px 16px; border: 1px solid #eef0f3; border-radius: 12px; background: #fafbfc; }
    .ws-q { font-size: 14px; font-weight: 700; color: #1f2a37; line-height: 1.4; }
    .ws-choices { display: grid; grid-template-columns: 1fr 0.8fr 1fr; gap: 8px; }
    .ws-choice { position: relative; display: flex; flex-direction: column; gap: 2px; padding: 9px 12px; border: 1.5px solid #e5e7eb; border-radius: 10px; background: #ffffff; cursor: pointer; transition: border-color .15s, background .15s; }
    .ws-choice:hover { border-color: #99f6e4; }
    .ws-choice input { position: absolute; opacity: 0; pointer-events: none; }
    .ws-choice-text { font-size: 13px; font-weight: 700; color: #1f2a37; }
    .ws-choice-hint { font-size: 11.5px; color: #6b7280; line-height: 1.35; }
    .ws-choice.is-mid { align-items: center; justify-content: center; text-align: center; }
    .ws-choice:has(input:checked) { border-color: var(--primary); background: var(--primary-light); }
    .ws-choice:has(input:checked) .ws-choice-text { color: var(--primary-dark); }
    .ws-choice:has(input:focus-visible) { outline: 3px solid #a8e1d9; outline-offset: 2px; }
    @media (max-width: 860px) {
        .ws-row { grid-template-columns: 1fr; }
    }
    @media (max-width: 520px) {
        .ws-choices { grid-template-columns: 1fr; }
        .ws-choice.is-mid { align-items: flex-start; text-align: left; }
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    // Kategori: tampilkan kolom ketik sendiri saat memilih "Lainnya"
    const sel = document.getElementById('categorySelect');
    const other = document.getElementById('categoryOther');
    if (sel && other) {
        const sync = () => {
            const isOther = sel.value === '__lainnya__';
            other.style.display = isOther ? '' : 'none';
            other.required = isOther;
            if (isOther && document.activeElement === sel) other.focus();
        };
        sel.addEventListener('change', sync);
        sync();
    }

    // Gaya kerja: hitung jawaban & izinkan batal pilih dengan klik ulang
    const counter = document.getElementById('wsCounter');
    const radios = document.querySelectorAll('.ws-choice input[type=radio]');
    const update = () => {
        const groups = new Set([...radios].map(r => r.name));
        const answered = [...groups].filter(n => document.querySelector(`.ws-choice input[name="${CSS.escape(n)}"]:checked`)).length;
        counter.textContent = `${answered}/${groups.size} dijawab`;
        counter.style.background = answered === groups.size ? '#e7f7f4' : '#f3f4f6';
        counter.style.color = answered === groups.size ? '#0c7566' : '#4b5563';
    };
    radios.forEach(r => {
        r.addEventListener('mousedown', () => { r.dataset.wasChecked = r.checked ? '1' : ''; });
        r.closest('label').addEventListener('mousedown', () => { r.dataset.wasChecked = r.checked ? '1' : ''; });
        r.addEventListener('click', () => {
            if (r.dataset.wasChecked === '1') { r.checked = false; r.dataset.wasChecked = ''; }
            update();
        });
        r.addEventListener('change', update);
    });
    update();
})();
</script>
@endpush

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
            <div style="width: 28px; height: 28px; border-radius: 50%; background: #0fa48f; color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">1</div>
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
                @php
                    $categoryOptions = \App\Models\Project::CATEGORY_OPTIONS;
                    $selectedCategory = old('category', $project->category);
                    $categoryOther = old('category_other', '');
                    if ($selectedCategory && $selectedCategory !== '__lainnya__' && !in_array($selectedCategory, $categoryOptions, true)) {
                        // Kategori lama yang tidak ada di daftar → tampilkan di kolom "Masukkan Kategori"
                        $categoryOther = $selectedCategory;
                        $selectedCategory = '__lainnya__';
                    }
                @endphp
                <select name="category" id="categorySelect" required
                        style="width: 100%; padding: 10px 14px; border: 1.5px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none; background: #ffffff;">
                    <option value="" disabled {{ $selectedCategory ? '' : 'selected' }}>Pilih kategori proyek</option>
                    @foreach($categoryOptions as $opt)
                        <option value="{{ $opt }}" {{ $selectedCategory === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                    <option value="__lainnya__" {{ $selectedCategory === '__lainnya__' ? 'selected' : '' }}>Lainnya (Masukkan Kategori)…</option>
                </select>
                <input type="text" name="category_other" id="categoryOther" value="{{ $categoryOther }}" maxlength="100"
                       placeholder="Ketik kategori, contoh: Kerajinan Tangan"
                       style="width: 100%; margin-top: 8px; padding: 10px 14px; border: 1.5px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none; {{ $selectedCategory === '__lainnya__' ? '' : 'display: none;' }}">
                @error('category')
                    <p style="margin: 6px 0 0; font-size: 12.5px; color: #dc2626;">{{ $message }}</p>
                @enderror
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
            <div style="width: 28px; height: 28px; border-radius: 50%; background: #0fa48f; color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">2</div>
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
    @php
        // Nilai (left/right) harus sama dengan WorkstyleMatchingService::CHARACTERISTIC_MAP
        $dimensions = [
            'independence_teamwork' => [
                'q' => 'Talenta akan lebih banyak bekerja…',
                'left' => 'Mandiri', 'left_text' => 'Sendiri', 'left_hint' => 'mengerjakan tugasnya sendiri',
                'right' => 'Kerja tim', 'right_text' => 'Bersama tim', 'right_hint' => 'bekerja bareng tim/rekan',
            ],
            'structure_flexibility' => [
                'q' => 'Bagaimana cara kerjanya diatur?',
                'left' => 'Terstruktur', 'left_text' => 'Ikuti arahan', 'left_hint' => 'ada langkah/SOP yang jelas',
                'right' => 'Fleksibel', 'right_text' => 'Bebas berkreasi', 'right_hint' => 'talenta menentukan caranya',
            ],
            'written_verbal' => [
                'q' => 'Komunikasi dengan Anda lebih sering lewat…',
                'left' => 'Tertulis', 'left_text' => 'Chat / pesan', 'left_hint' => 'WhatsApp, email, dokumen',
                'right' => 'Langsung', 'right_text' => 'Ngobrol langsung', 'right_hint' => 'tatap muka atau telepon',
            ],
            'remote_field' => [
                'q' => 'Di mana pekerjaan dilakukan?',
                'left' => 'Remote', 'left_text' => 'Online / dari rumah', 'left_hint' => 'cukup pakai laptop/HP',
                'right' => 'Lapangan', 'right_text' => 'Di lokasi', 'right_hint' => 'datang ke tempat usaha/acara',
            ],
            'analytical_practical' => [
                'q' => 'Tugasnya lebih banyak berupa…',
                'left' => 'Analitis', 'left_text' => 'Olah data / dokumen', 'left_hint' => 'teliti, di depan layar',
                'right' => 'Praktis', 'right_text' => 'Kerja aktif', 'right_hint' => 'aksi langsung, banyak bergerak',
            ],
            'stable_dynamic' => [
                'q' => 'Ritme kerjanya seperti apa?',
                'left' => 'Stabil', 'left_text' => 'Tenang & rutin', 'left_hint' => 'tugas mirip setiap hari',
                'right' => 'Dinamis', 'right_text' => 'Cepat & berubah-ubah', 'right_hint' => 'sering ada hal mendadak',
            ],
            'limited_intense_interaction' => [
                'q' => 'Seberapa sering talenta bertemu orang baru?',
                'left' => 'Interaksi terbatas', 'left_text' => 'Jarang', 'left_hint' => 'fokus pada pekerjaan sendiri',
                'right' => 'Interaksi intens', 'right_text' => 'Sering', 'right_hint' => 'melayani pelanggan/pengunjung',
            ],
            'detailed_spontaneous_planning' => [
                'q' => 'Rencana kerjanya…',
                'left' => 'Rencana rinci', 'left_text' => 'Sudah dijadwalkan', 'left_hint' => 'jadwal & target jelas',
                'right' => 'Spontan', 'right_text' => 'Menyesuaikan situasi', 'right_hint' => 'diatur sambil jalan',
            ],
        ];
        $savedCharacteristics = $project->workstyle_characteristics ?? [];
    @endphp

    <div style="background: #ffffff; border: 1px solid #e9edf2; border-radius: 14px; padding: 28px; margin-bottom: 28px; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px; padding-bottom: 14px; border-bottom: 1px solid #f3f4f6;">
            <div style="width: 28px; height: 28px; border-radius: 50%; background: #0fa48f; color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; flex-shrink: 0;">3</div>
            <div style="flex: 1;">
                <h2 style="font-size: 17px; font-weight: 700; color: #111827; margin: 0 0 2px;">Suasana Kerja Proyek <span style="font-size: 12.5px; font-weight: 600; color: #9ca3af;">(opsional)</span></h2>
                <p style="margin: 0; font-size: 13px; color: #6b7280;">
                    Jawab singkat seperti apa pekerjaannya nanti. TANDANG memakai jawaban ini untuk mencocokkan proyek dengan talenta yang gaya kerjanya sesuai.
                </p>
            </div>
            <span id="wsCounter" style="flex-shrink: 0; background: #f3f4f6; color: #4b5563; font-size: 12px; font-weight: 700; padding: 5px 10px; border-radius: 999px;">0/8 dijawab</span>
        </div>

        <div style="background: #f0fdfa; border: 1px solid #ccfbf1; color: #0f766e; font-size: 12.5px; border-radius: 10px; padding: 10px 14px; margin-bottom: 18px;">
            Tidak yakin? Pilih <strong>Keduanya</strong>, atau lewati saja. Klik pilihan yang sama sekali lagi untuk membatalkannya.
        </div>

        <div class="ws-list">
            @foreach($dimensions as $key => $dim)
                @php $currentVal = old("workstyle_characteristics.{$key}", $savedCharacteristics[$key] ?? ''); @endphp
                <div class="ws-row">
                    <div class="ws-q">{{ $loop->iteration }}. {{ $dim['q'] }}</div>
                    <div class="ws-choices" role="radiogroup" aria-label="{{ $dim['q'] }}">
                        @foreach([
                            ['value' => $dim['left'], 'text' => $dim['left_text'], 'hint' => $dim['left_hint']],
                            ['value' => 'Keduanya', 'text' => 'Keduanya', 'hint' => 'campuran / tidak tentu'],
                            ['value' => $dim['right'], 'text' => $dim['right_text'], 'hint' => $dim['right_hint']],
                        ] as $choice)
                            <label class="ws-choice {{ $choice['value'] === 'Keduanya' ? 'is-mid' : '' }}">
                                <input type="radio" name="workstyle_characteristics[{{ $key }}]" value="{{ $choice['value'] }}"
                                       {{ $currentVal === $choice['value'] ? 'checked' : '' }}>
                                <span class="ws-choice-text">{{ $choice['text'] }}</span>
                                <span class="ws-choice-hint">{{ $choice['hint'] }}</span>
                            </label>
                        @endforeach
                    </div>
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
