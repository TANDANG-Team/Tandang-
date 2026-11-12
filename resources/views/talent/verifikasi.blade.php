@extends('talent.layout')

@section('title', 'Verifikasi Akun Talenta')

@section('content')
<div class="page-head">
    <div>
        <h1>Verifikasi Talenta Muda</h1>
        <p>Verifikasi status pelajar, mahasiswa, atau fresh graduate dengan sistem screening otomatis dan validasi dokumen resmi.</p>
    </div>
</div>

<div style="max-width: 780px;">
    <!-- Status Card -->
    <div class="dash-card" style="margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <!-- Badge Status Verifikasi -->
                @php
                    $vStatus = $talent->verification_status;
                    $eStatus = $talent->eligibility_status;

                    $vBadgeClass = match($vStatus) {
                        'verified', 'Terverifikasi' => 'badge-success',
                        'pending', 'Menunggu Verifikasi' => 'badge-warning',
                        'rejected', 'Ditolak' => 'badge-danger',
                        'expired' => 'badge-danger',
                        default => 'badge-info',
                    };

                    $eBadgeClass = match($eStatus) {
                        'eligible' => 'badge-success',
                        'ineligible' => 'badge-danger',
                        default => 'badge-info',
                    };
                @endphp
                <span class="badge {{ $vBadgeClass }}">
                    Status: {{ $talent->verification_status_label }}
                </span>
                <span class="badge {{ $eBadgeClass }}">
                    Kelayakan: {{ $talent->eligibility_status_label }}
                </span>
            </div>

            <small style="color: var(--muted); font-size: 12px;">
                {{ $talent->submitted_at ? 'Pengajuan terakhir: ' . $talent->submitted_at->format('d M Y, H:i') : 'Belum pernah mengajukan' }}
            </small>
        </div>

        @if($vStatus === 'verified' || $vStatus === 'Terverifikasi')
            <div style="background: var(--success-light); border: 1px solid #a7f3d0; border-radius: 10px; padding: 16px 20px; color: #065f46; font-size: 13.5px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <strong style="font-size: 15px;">Akun Anda Telah Terverifikasi!</strong>
                </div>
                <p style="font-size: 13px; line-height: 1.6;">
                    Dokumen Anda telah divalidasi dan disetujui oleh Administrator
                    @if($talent->verified_at) pada {{ $talent->verified_at->format('d M Y, H:i') }} @endif.
                    Anda memiliki hak penuh untuk melamar seluruh lowongan proyek yang tersedia.
                </p>

                @if($talent->has_physical_document)
                    <div style="margin-top: 12px; padding-top: 10px; border-top: 1px solid #bbf7d0; display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-size: 12.5px;">Berkas Terverifikasi: <strong>{{ $talent->document_original_name }}</strong> ({{ $talent->document_size_formatted }})</span>
                        <a href="{{ route('talent.dokumen') }}" target="_blank" class="btn btn-outline btn-sm" style="background:#fff;">Lihat Dokumen PDF &rarr;</a>
                    </div>
                @endif
            </div>

        @elseif($vStatus === 'pending' || $vStatus === 'Menunggu Verifikasi')
            <div style="background: var(--amber-light); border: 1px solid #fde68a; border-radius: 10px; padding: 16px 20px; color: #92400e; font-size: 13.5px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <strong style="font-size: 15px;">Dokumen Sedang Dalam Peninjauan Admin</strong>
                </div>
                <p style="font-size: 13px; line-height: 1.6;">
                    Permohonan verifikasi dan dokumen PDF Anda telah kami terima dan lolos screening kelayakan awal. Tim admin saat ini sedang memeriksa keaslian dan kesesuaian berkas.
                </p>

                @if(!empty($talent->screening_notes))
                    <div style="margin-top: 10px; background: rgba(255,255,255,0.7); border-radius: 6px; padding: 8px 12px; font-size: 12.5px;">
                        <strong>Catatan Screening Otomatis:</strong> {{ $talent->screening_notes }}
                    </div>
                @endif

                @if($talent->has_physical_document)
                    <div style="margin-top: 14px; padding-top: 10px; border-top: 1px solid #fcd34d; display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-size: 12.5px;">Berkas: <strong>{{ $talent->document_original_name }}</strong> ({{ $talent->document_size_formatted }})</span>
                        <a href="{{ route('talent.dokumen') }}" target="_blank" class="btn btn-secondary btn-sm" style="background:#fff;">Lihat Dokumen PDF &rarr;</a>
                    </div>
                @endif
            </div>

        @elseif($vStatus === 'rejected' || $vStatus === 'Ditolak')
            <div style="background: var(--danger-light); border: 1px solid #fecaca; border-radius: 10px; padding: 16px 20px; color: #991b1b; font-size: 13.5px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                    <strong style="font-size: 15px;">Pengajuan Verifikasi Ditolak</strong>
                </div>
                <p style="font-size: 13px; line-height: 1.6; font-weight: 600; margin-top: 4px;">
                    Alasan Penolakan: "{{ $talent->rejection_reason ?: 'Dokumen tidak valid atau tidak memenuhi syarat ketentuan Talenta Muda.' }}"
                </p>

                @if(!empty($talent->screening_notes))
                    <p style="margin-top: 6px; font-size: 12px; opacity: 0.9;">
                        Catatan Sistem: {{ $talent->screening_notes }}
                    </p>
                @endif

                <p style="margin-top: 8px; font-size: 12.5px;">
                    Anda dapat memperbaiki isian data atau mengunggah ulang dokumen PDF yang valid melalui formulir di bawah.
                </p>
            </div>

        @elseif($vStatus === 'expired')
            <div style="background: var(--danger-light); border: 1px solid #fecaca; border-radius: 10px; padding: 16px 20px; color: #991b1b; font-size: 13.5px;">
                <strong style="font-size: 15px;">Masa Kelayakan Akun Kedaluwarsa</strong>
                <p style="margin-top: 4px; font-size: 13px; line-height: 1.6;">
                    {{ $talent->rejection_reason ?: 'Masa kelulusan Anda telah melebihi batas 24 bulan untuk kategori Fresh Graduate.' }}
                </p>
            </div>

        @else
            <p style="font-size: 13.5px; color: var(--muted); line-height: 1.6;">
                Lengkapi formulir pendidikan di bawah dan unggah dokumen bukti resmi (KTM, Kartu Pelajar, atau Ijazah/SKL kelulusan maksimal 2 tahun) dalam format <strong>PDF (maks. 2 MB)</strong> untuk mendapatkan verifikasi resmi.
            </p>
        @endif
    </div>

    <!-- Form Pengajuan Verifikasi (Hanya muncul jika belum verified & tidak pending) -->
    @if($vStatus !== 'verified' && $vStatus !== 'Terverifikasi' && $vStatus !== 'pending' && $vStatus !== 'Menunggu Verifikasi')
        <div class="table-card" style="padding: 28px;">
            <div style="margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid var(--line);">
                <h3 style="font-size: 17px; font-weight: 800; color: var(--navy);">
                    {{ $vStatus === 'rejected' || $vStatus === 'Ditolak' ? 'Ajukan Ulang Dokumen Verifikasi' : 'Formulir Pengajuan Verifikasi' }}
                </h3>
                <p style="font-size: 13px; color: var(--muted); margin-top: 4px;">
                    Sistem akan memvalidasi kelayakan data secara otomatis sebelum diteruskan ke Admin.
                </p>
            </div>

            @if($errors->any())
                <div class="alert alert-danger" style="margin-bottom: 20px;">
                    <ul style="margin-left: 18px; font-size: 13px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="verifForm" method="POST" action="{{ route('talent.verifikasi.ajukan') }}" enctype="multipart/form-data">
                @csrf

                <!-- Pilihan Kategori Talenta -->
                <div class="form-group">
                    <label for="category">Kategori Talenta <span style="color: var(--danger);">*</span></label>
                    <select name="category" id="category" class="form-control" required onchange="handleCategoryChange(this.value)">
                        <option value="Siswa SMK/SMA" {{ old('category', $talent->category) === 'Siswa SMK/SMA' ? 'selected' : '' }}>Siswa SMK Aktif (atau SMA)</option>
                        <option value="Mahasiswa Aktif" {{ old('category', $talent->category) === 'Mahasiswa Aktif' || !$talent->category ? 'selected' : '' }}>Mahasiswa Aktif</option>
                        <option value="Fresh Graduate (≤ 2 Tahun)" {{ old('category', $talent->category) === 'Fresh Graduate (≤ 2 Tahun)' ? 'selected' : '' }}>Fresh Graduate (Maksimal 2 Tahun Kelulusan)</option>
                    </select>
                </div>

                <!-- Fields Umum -->
                <div class="form-group">
                    <label for="institution" id="labelInstitution">Nama Institusi / Perguruan Tinggi <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="institution" id="institution" class="form-control" value="{{ old('institution', $talent->institution !== '-' ? $talent->institution : '') }}" placeholder="Contoh: Universitas Indonesia" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label for="major" id="labelMajor">Jurusan / Program Studi <span style="color: var(--danger);">*</span></label>
                        <input type="text" name="major" id="major" class="form-control" value="{{ old('major', $talent->major !== '-' ? $talent->major : '') }}" placeholder="Contoh: Ilmu Komputer" required>
                    </div>

                    <div class="form-group">
                        <label for="city">Kota Institusi <span style="color: var(--danger);">*</span></label>
                        <input type="text" name="city" id="city" class="form-control" value="{{ old('city', $talent->city) }}" placeholder="Contoh: Bandung, Depok, Jakarta" required>
                    </div>
                </div>

                <!-- Dynamic Fields: Siswa SMK -->
                <div id="dynamicSmk" style="display: none; background: #f8fafc; border: 1px solid var(--line); border-radius: 10px; padding: 18px; margin-bottom: 20px;">
                    <h4 style="font-size: 13.5px; font-weight: 700; color: var(--teal-dark); margin-bottom: 12px;">Detail Data Siswa SMK:</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="class_level">Kelas Saat Ini <span style="color: var(--danger);">*</span></label>
                            <select name="class_level" id="class_level" class="form-control">
                                <option value="X" {{ old('class_level', $talent->class_level) === 'X' ? 'selected' : '' }}>Kelas X</option>
                                <option value="XI" {{ old('class_level', $talent->class_level) === 'XI' ? 'selected' : '' }}>Kelas XI</option>
                                <option value="XII" {{ old('class_level', $talent->class_level) === 'XII' || !$talent->class_level ? 'selected' : '' }}>Kelas XII</option>
                                <option value="XIII" {{ old('class_level', $talent->class_level) === 'XIII' ? 'selected' : '' }}>Kelas XIII (SMK 4 Tahun)</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="academic_year_smk">Tahun Ajaran <span style="color: var(--danger);">*</span></label>
                            <input type="text" name="academic_year" id="academic_year_smk" class="form-control" value="{{ old('academic_year', $talent->academic_year ?: '2025/2026') }}" placeholder="Contoh: 2025/2026">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="graduation_year_smk">Perkiraan Lulus <span style="color: var(--danger);">*</span></label>
                            <input type="number" name="graduation_year" id="graduation_year_smk" class="form-control" value="{{ old('graduation_year', $talent->graduation_year ?: date('Y') + 1) }}" placeholder="Contoh: 2026">
                        </div>
                    </div>
                </div>

                <!-- Dynamic Fields: Mahasiswa Aktif -->
                <div id="dynamicMahasiswa" style="display: none; background: #f8fafc; border: 1px solid var(--line); border-radius: 10px; padding: 18px; margin-bottom: 20px;">
                    <h4 style="font-size: 13.5px; font-weight: 700; color: var(--teal-dark); margin-bottom: 12px;">Detail Data Mahasiswa Aktif:</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="entry_year">Tahun Masuk <span style="color: var(--danger);">*</span></label>
                            <input type="number" name="entry_year" id="entry_year" class="form-control" value="{{ old('entry_year', $talent->entry_year ?: date('Y') - 2) }}" placeholder="Contoh: 2023">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="current_semester">Semester Saat Ini <span style="color: var(--danger);">*</span></label>
                            <input type="number" name="current_semester" id="current_semester" class="form-control" value="{{ old('current_semester', $talent->current_semester ?: 4) }}" min="1" max="14" placeholder="Contoh: 4">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="academic_year_mhs">Tahun Akademik <span style="color: var(--danger);">*</span></label>
                            <input type="text" name="academic_year" id="academic_year_mhs" class="form-control" value="{{ old('academic_year', $talent->academic_year ?: '2025/2026') }}" placeholder="Contoh: 2025/2026">
                        </div>
                    </div>
                </div>

                <!-- Dynamic Fields: Fresh Graduate -->
                <div id="dynamicFresh" style="display: none; background: #f8fafc; border: 1px solid var(--line); border-radius: 10px; padding: 18px; margin-bottom: 20px;">
                    <h4 style="font-size: 13.5px; font-weight: 700; color: var(--teal-dark); margin-bottom: 8px;">Detail Kelulusan (Maksimal 24 Bulan):</h4>
                    <p style="font-size: 12px; color: var(--muted); margin-bottom: 12px;">Sistem akan menghitung selisih bulan secara otomatis per hari ini.</p>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="graduation_month">Bulan Kelulusan <span style="color: var(--danger);">*</span></label>
                            <select name="graduation_month" id="graduation_month" class="form-control">
                                @php
                                    $months = [
                                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                                    ];
                                    $savedMonth = old('graduation_month', $talent->graduation_month ?: date('n'));
                                @endphp
                                @foreach($months as $num => $name)
                                    <option value="{{ $num }}" {{ (int)$savedMonth === $num ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="graduation_year_fg">Tahun Kelulusan <span style="color: var(--danger);">*</span></label>
                            <input type="number" name="graduation_year" id="graduation_year_fg" class="form-control" value="{{ old('graduation_year', $talent->graduation_year ?: date('Y') - 1) }}" placeholder="Contoh: 2024">
                        </div>
                    </div>
                </div>

                <!-- Upload Dokumen PDF Sebenarnya -->
                <div class="form-group" style="margin-top: 10px;">
                    <label for="documentFile">Unggah Dokumen Pendukung (PDF) <span style="color: var(--danger);">*</span></label>
                    <p style="font-size: 12px; color: var(--muted); margin-bottom: 10px;">
                        KTM aktif (Mahasiswa), Kartu Pelajar (Siswa SMK), atau Ijazah / Surat Keterangan Lulus / SKL (Fresh Graduate). File <strong>PDF asli</strong>, maksimal <strong>2 MB (2048 KB)</strong>.
                    </p>

                    <div id="fileDropArea" style="border: 2px dashed #cbd5e1; border-radius: 12px; padding: 24px; text-align: center; background: #f8fafc; cursor: pointer; transition: all 0.2s;">
                        <input type="file" name="document" id="documentFile" accept="application/pdf" style="display: none;" onchange="handleFileSelected(this)">

                        <div id="fileUploadPrompt">
                            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="color: var(--teal); margin-bottom: 8px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            <p style="font-size: 13.5px; font-weight: 700; color: var(--navy); margin-bottom: 4px;">Klik untuk memilih berkas PDF</p>
                            <span style="font-size: 12px; color: var(--muted);">Format file: .pdf · Maksimal 2 MB</span>
                        </div>

                        <div id="fileSelectedInfo" style="display: none; background: #fff; border: 1px solid #99f6e4; border-radius: 8px; padding: 12px 16px; margin-top: 6px;">
                            <div style="display: flex; align-items: center; justify-content: center; gap: 10px;">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0f9d8a" stroke-width="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                                <div style="text-align: left;">
                                    <strong id="displayFileName" style="font-size: 13px; color: var(--navy);"></strong><br>
                                    <span id="displayFileSize" style="font-size: 11.5px; color: var(--muted);"></span>
                                </div>
                            </div>
                        </div>

                        <div id="fileErrorBox" style="display: none; color: var(--danger); font-size: 12.5px; font-weight: 700; margin-top: 10px;"></div>
                    </div>
                </div>

                <div style="margin-top: 24px;">
                    <button type="submit" id="btnSubmitVerif" class="btn btn-primary" style="padding: 11px 24px; font-size: 14px;">
                        {{ $vStatus === 'rejected' || $vStatus === 'Ditolak' ? 'Kirim Ulang Permohonan Verifikasi' : 'Kirim Berkas Verifikasi' }}
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>

@push('scripts')
<script>
function handleCategoryChange(val) {
    const smkBox = document.getElementById('dynamicSmk');
    const mhsBox = document.getElementById('dynamicMahasiswa');
    const fgBox = document.getElementById('dynamicFresh');
    const labelInst = document.getElementById('labelInstitution');
    const labelMajor = document.getElementById('labelMajor');

    // Disable inputs inside inactive sections so they don't submit empty fields
    toggleSectionInputs(smkBox, false);
    toggleSectionInputs(mhsBox, false);
    toggleSectionInputs(fgBox, false);

    if (val === 'Siswa SMK/SMA') {
        smkBox.style.display = 'block';
        mhsBox.style.display = 'none';
        fgBox.style.display = 'none';
        toggleSectionInputs(smkBox, true);
        labelInst.innerHTML = 'Nama Sekolah SMK / SMA <span style="color: var(--danger);">*</span>';
        labelMajor.innerHTML = 'Jurusan / Keahlian <span style="color: var(--danger);">*</span>';
    } else if (val === 'Fresh Graduate (≤ 2 Tahun)') {
        smkBox.style.display = 'none';
        mhsBox.style.display = 'none';
        fgBox.style.display = 'block';
        toggleSectionInputs(fgBox, true);
        labelInst.innerHTML = 'Nama Perguruan Tinggi / Sekolah Terakhir <span style="color: var(--danger);">*</span>';
        labelMajor.innerHTML = 'Jurusan / Program Studi <span style="color: var(--danger);">*</span>';
    } else { // Mahasiswa Aktif
        smkBox.style.display = 'none';
        mhsBox.style.display = 'block';
        fgBox.style.display = 'none';
        toggleSectionInputs(mhsBox, true);
        labelInst.innerHTML = 'Nama Universitas / Perguruan Tinggi <span style="color: var(--danger);">*</span>';
        labelMajor.innerHTML = 'Program Studi <span style="color: var(--danger);">*</span>';
    }
}

function toggleSectionInputs(container, enabled) {
    if (!container) return;
    const inputs = container.querySelectorAll('input, select');
    inputs.forEach(inp => {
        inp.disabled = !enabled;
    });
}

// File picker area click
const dropArea = document.getElementById('fileDropArea');
const fileInput = document.getElementById('documentFile');
if (dropArea && fileInput) {
    dropArea.addEventListener('click', () => fileInput.click());
}

function handleFileSelected(input) {
    const file = input.files[0];
    const errorBox = document.getElementById('fileErrorBox');
    const infoBox = document.getElementById('fileSelectedInfo');
    const promptBox = document.getElementById('fileUploadPrompt');
    const submitBtn = document.getElementById('btnSubmitVerif');

    errorBox.style.display = 'none';
    errorBox.innerText = '';

    if (!file) {
        infoBox.style.display = 'none';
        promptBox.style.display = 'block';
        return;
    }

    // Validasi tipe PDF
    if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
        errorBox.innerText = 'Format berkas tidak sesuai. Hanya file PDF (.pdf) yang diperbolehkan.';
        errorBox.style.display = 'block';
        infoBox.style.display = 'none';
        input.value = '';
        submitBtn.disabled = true;
        return;
    }

    // Validasi ukuran maksimal 2 MB (2097152 bytes)
    const maxBytes = 2 * 1024 * 1024;
    if (file.size > maxBytes) {
        errorBox.innerText = `Ukuran berkas (${(file.size / (1024 * 1024)).toFixed(2)} MB) melebihi batas maksimal 2 MB.`;
        errorBox.style.display = 'block';
        infoBox.style.display = 'none';
        input.value = '';
        submitBtn.disabled = true;
        return;
    }

    // File valid
    submitBtn.disabled = false;
    document.getElementById('displayFileName').innerText = file.name;
    document.getElementById('displayFileSize').innerText = (file.size / 1024).toFixed(1) + ' KB';
    infoBox.style.display = 'block';
    promptBox.style.display = 'none';
}

// Form submit loading state
const verifForm = document.getElementById('verifForm');
if (verifForm) {
    verifForm.addEventListener('submit', function() {
        const btn = document.getElementById('btnSubmitVerif');
        btn.disabled = true;
        btn.innerText = 'Mengunggah & Memeriksa Kelayakan...';
    });
}

// Inisialisasi dynamic form saat halaman pertama kali dimuat
document.addEventListener('DOMContentLoaded', function() {
    const cat = document.getElementById('category');
    if (cat) {
        handleCategoryChange(cat.value);
    }
});
</script>
@endpush
@endsection
