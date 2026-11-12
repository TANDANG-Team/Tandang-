<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Talenta - Admin Panel | TANDANG</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <style>
        .badge-eligible { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; font-size: 11px; padding: 3px 8px; border-radius: 99px; font-weight: 700; }
        .badge-ineligible { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; font-size: 11px; padding: 3px 8px; border-radius: 99px; font-weight: 700; }
        .badge-undetermined { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; font-size: 11px; padding: 3px 8px; border-radius: 99px; font-weight: 700; }
        .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin: 14px 0; font-size: 13px; }
        .detail-item { background: #f8fafc; padding: 10px 12px; border-radius: 8px; border: 1px solid #f1f5f9; }
        .detail-item strong { display: block; font-size: 11px; color: #64748b; text-transform: uppercase; margin-bottom: 2px; }
        .btn-pdf { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: #e7f7f4; color: #0c7566; border: 1px solid #99f6e4; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 700; transition: all 0.2s; }
        .btn-pdf:hover { background: #0fa48f; color: #fff; }
        .btn-pdf.disabled { background: #f1f5f9; color: #94a3b8; border-color: #e2e8f0; cursor: not-allowed; }
    </style>
</head>
<body>
    <div class="app-shell">
        <!-- Sidebar Navigation -->
        <aside class="app-sidebar">
            <a href="/" class="sidebar-logo">
                <div class="logo-bars">
                    <span class="logo-bar bar-1"></span>
                    <span class="logo-bar bar-2"></span>
                    <span class="logo-bar bar-3"></span>
                </div>
                <span class="logo-text">TANDANG</span>
            </a>

            <div class="role-badge-tag">Admin Panel</div>

            <nav class="sidebar-nav">
                <a href="{{ route('admin.verifikasi.index') }}" class="active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        <path d="m9 12 2 2 4-4"/>
                    </svg>
                    Verifikasi Talenta
                    @if(($counts['pending'] ?? 0) > 0)
                        <span class="badge-counter">{{ $counts['pending'] }}</span>
                    @endif
                </a>

                <a href="{{ route('admin.pengguna.index') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    Kelola Pengguna
                </a>

                <a href="{{ route('admin.proyek.index') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="20" height="14" x="2" y="7" rx="2" ry="2"/>
                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                    </svg>
                    Kelola Proyek
                </a>

                <a href="/" style="margin-top: 20px; color: #94a3b8;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                    Kembali ke Beranda
                </a>
            </nav>

            <div class="side-user">
                <div class="avatar">{{ strtoupper(substr(Auth::user()->name ?? 'A', 0, 1)) }}</div>
                <div class="side-user-info">
                    <strong>{{ Auth::user()->name ?? 'Administrator' }}</strong>
                    <small>{{ Auth::user()->email ?? 'admin@tandang.id' }}</small>
                </div>
                <form action="{{ route('logout') }}" method="POST" style="margin-left: auto;">
                    @csrf
                    <button type="submit" title="Keluar / Logout" style="border: 0; background: transparent; color: #94a3b8; cursor: pointer; display: flex; align-items: center; padding: 6px; border-radius: 6px; transition: all 0.15s ease;" onmouseover="this.style.color='#ef4444'; this.style.background='#fee2e2';" onmouseout="this.style.color='#94a3b8'; this.style.background='transparent';">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Dashboard Container -->
        <div class="dash-container">
            <header class="dash-header">
                <span style="font-size: 13px; font-weight: 700; color: #64748b;">
                    Moderasi Verifikasi Dokumen Talenta
                </span>
                <span class="status status-verified">Sistem Screening Aktif</span>
            </header>

            <main class="dash-main">
                <div class="page-head">
                    <h1>Verifikasi Talenta</h1>
                    <p>Screening kelayakan otomatis berbasis aturan (SMK, Mahasiswa Aktif, Fresh Graduate &le; 24 bulan) dengan pemeriksaan dokumen PDF fisik.</p>
                </div>

                <!-- Toast Alerts -->
                @if(session('success'))
                    <div class="toast-box toast-success">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('warning'))
                    <div class="toast-box toast-warning">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        {{ session('warning') }}
                    </div>
                @endif

                <!-- Management Toolbar: Filter Status, Filter Kategori, & Search -->
                <div class="management-toolbar" style="flex-wrap: wrap; gap: 14px;">
                    <div class="filter-pills">
                        <a href="{{ route('admin.verifikasi.index', ['status' => 'Semua', 'category' => $currentCategory, 'q' => $searchQuery]) }}" class="{{ $currentFilter === 'Semua' ? 'active' : '' }}">
                            Semua <span>{{ $counts['Semua'] ?? 0 }}</span>
                        </a>
                        <a href="{{ route('admin.verifikasi.index', ['status' => 'pending', 'category' => $currentCategory, 'q' => $searchQuery]) }}" class="{{ $currentFilter === 'pending' || $currentFilter === 'Menunggu Verifikasi' ? 'active' : '' }}">
                            Menunggu Verifikasi <span>{{ $counts['pending'] ?? 0 }}</span>
                        </a>
                        <a href="{{ route('admin.verifikasi.index', ['status' => 'verified', 'category' => $currentCategory, 'q' => $searchQuery]) }}" class="{{ $currentFilter === 'verified' || $currentFilter === 'Terverifikasi' ? 'active' : '' }}">
                            Terverifikasi <span>{{ $counts['verified'] ?? 0 }}</span>
                        </a>
                        <a href="{{ route('admin.verifikasi.index', ['status' => 'rejected', 'category' => $currentCategory, 'q' => $searchQuery]) }}" class="{{ $currentFilter === 'rejected' || $currentFilter === 'Ditolak' ? 'active' : '' }}">
                            Ditolak <span>{{ $counts['rejected'] ?? 0 }}</span>
                        </a>
                        <a href="{{ route('admin.verifikasi.index', ['status' => 'expired', 'category' => $currentCategory, 'q' => $searchQuery]) }}" class="{{ $currentFilter === 'expired' ? 'active' : '' }}">
                            Kedaluwarsa <span>{{ $counts['expired'] ?? 0 }}</span>
                        </a>
                    </div>

                    <form method="GET" action="{{ route('admin.verifikasi.index') }}" style="display: flex; gap: 10px; align-items: center; margin-left: auto;">
                        <input type="hidden" name="status" value="{{ $currentFilter }}">

                        <select name="category" onchange="this.form.submit()" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--line); font-size: 13px; font-family: inherit; background: #fff;">
                            <option value="Semua">Semua Kategori</option>
                            <option value="Siswa SMK" {{ $currentCategory === 'Siswa SMK' ? 'selected' : '' }}>Siswa SMK</option>
                            <option value="Mahasiswa" {{ $currentCategory === 'Mahasiswa' ? 'selected' : '' }}>Mahasiswa Aktif</option>
                            <option value="Fresh Graduate" {{ $currentCategory === 'Fresh Graduate' ? 'selected' : '' }}>Fresh Graduate</option>
                        </select>

                        <div class="searchbox">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"/>
                                <path d="m21 21-4.3-4.3"/>
                            </svg>
                            <input type="text" name="q" value="{{ $searchQuery }}" placeholder="Cari nama, kampus, jurusan...">
                        </div>
                    </form>
                </div>

                <!-- Table Card -->
                <div class="table-card">
                    @if($talents->count() > 0)
                        <table>
                            <thead>
                                <tr>
                                    <th>Nama Talenta</th>
                                    <th>Kategori</th>
                                    <th>Institusi</th>
                                    <th>Status Kelayakan</th>
                                    <th>Status Verifikasi</th>
                                    <th>Tanggal Pengajuan</th>
                                    <th style="text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($talents as $talent)
                                    @php
                                        $vStatus = $talent->verification_status;
                                        $eStatus = $talent->eligibility_status;

                                        $vClass = match($vStatus) {
                                            'verified', 'Terverifikasi' => 'status-verified',
                                            'rejected', 'Ditolak' => 'status-rejected',
                                            'expired' => 'status-rejected',
                                            default => 'status-pending',
                                        };

                                        $eClass = match($eStatus) {
                                            'eligible' => 'badge-eligible',
                                            'ineligible' => 'badge-ineligible',
                                            default => 'badge-undetermined',
                                        };
                                    @endphp
                                    <tr>
                                        <td>
                                            <b>{{ $talent->user->name ?? 'Pengguna' }}</b>
                                            <small>{{ $talent->user->email ?? '—' }}</small>
                                        </td>
                                        <td>
                                            <span style="font-size: 12.5px; font-weight: 700; color: #475569;">
                                                {{ $talent->category }}
                                            </span>
                                        </td>
                                        <td>
                                            <b>{{ $talent->institution ?: 'Belum diisi' }}</b>
                                            <small>{{ $talent->major ? $talent->major : '—' }} ({{ $talent->city ?: '-' }})</small>
                                        </td>
                                        <td>
                                            <span class="{{ $eClass }}">
                                                {{ $talent->eligibility_status_label }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status {{ $vClass }}">
                                                {{ $talent->verification_status_label }}
                                            </span>
                                        </td>
                                        <td>
                                            {{ $talent->submitted_at ? $talent->submitted_at->locale('id')->isoFormat('D MMM Y, HH:mm') : '—' }}
                                        </td>
                                        <td style="text-align: center;">
                                            <div style="display: inline-flex; align-items: center; gap: 8px;">
                                                <!-- Tombol Buka PDF Langsung (Jika ada file fisik) -->
                                                @if($talent->has_physical_document)
                                                    <a href="{{ route('admin.verifikasi.dokumen', $talent->id) }}" target="_blank" class="btn-pdf" title="Buka PDF di Tab Baru">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                                                        PDF
                                                    </a>
                                                @else
                                                    <span class="btn-pdf disabled" title="Dokumen fisik belum diunggah (data demo)">
                                                        PDF
                                                    </span>
                                                @endif

                                                <!-- Tombol Lihat Detail Modal -->
                                                <button type="button" 
                                                        class="icon-action" 
                                                        title="Lihat Detail & Keputusan"
                                                        onclick="openVerifyModal({{ json_encode([
                                                            'id' => $talent->id,
                                                            'name' => $talent->user->name ?? 'Pengguna',
                                                            'email' => $talent->user->email ?? '',
                                                            'category' => $talent->category,
                                                            'institution' => $talent->institution ?: '-',
                                                            'major' => $talent->major ?: '-',
                                                            'city' => $talent->city ?: '-',
                                                            'class_level' => $talent->class_level,
                                                            'academic_year' => $talent->academic_year,
                                                            'graduation_year' => $talent->graduation_year,
                                                            'entry_year' => $talent->entry_year,
                                                            'current_semester' => $talent->current_semester,
                                                            'graduation_month' => $talent->graduation_month,
                                                            'eligibility_status' => $talent->eligibility_status,
                                                            'eligibility_label' => $talent->eligibility_status_label,
                                                            'screening_notes' => $talent->screening_notes ?: 'Screening otomatis belum dilakukan.',
                                                            'has_physical_pdf' => $talent->has_physical_document,
                                                            'document_name' => $talent->document_original_name ?: ($talent->document ?: 'Belum ada dokumen'),
                                                            'document_size' => $talent->document_size_formatted,
                                                            'pdf_url' => $talent->has_physical_document ? route('admin.verifikasi.dokumen', $talent->id) : null,
                                                            'verification_status' => $talent->verification_status,
                                                            'verification_label' => $talent->verification_status_label,
                                                            'submitted_at' => $talent->submitted_at ? $talent->submitted_at->locale('id')->isoFormat('D MMMM Y, HH:mm') : '-',
                                                            'verified_at' => $talent->verified_at ? $talent->verified_at->locale('id')->isoFormat('D MMMM Y, HH:mm') : null,
                                                            'reviewer' => $talent->reviewer->name ?? null,
                                                            'rejection_reason' => $talent->rejection_reason,
                                                            'approve_url' => route('admin.verifikasi.approve', $talent->id),
                                                            'reject_url' => route('admin.verifikasi.reject', $talent->id),
                                                        ]) }})">
                                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                                                        <circle cx="12" cy="12" r="3"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="empty-state">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="m9 12 2 2 4-4"/>
                            </svg>
                            <p style="font-weight: 700; color: #1e293b;">Tidak ada permohonan verifikasi</p>
                            <p style="font-size: 12px; margin-top: 4px;">Tidak ditemukan talenta pada filter atau kata kunci ini.</p>
                        </div>
                    @endif
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Detail & Aksi Verifikasi Komprehensif -->
    <div id="verifyModal" class="modal-backdrop">
        <div class="modal-box" style="max-width: 650px;">
            <div class="modal-header">
                <h2>Detail Pengajuan Verifikasi</h2>
                <button type="button" class="modal-close" onclick="closeVerifyModal()">&times;</button>
            </div>

            <div class="talent-modal-head">
                <div class="avatar" id="modalAvatar">T</div>
                <div>
                    <h3 id="modalName">Nama Talenta</h3>
                    <p id="modalCategoryAndInst">Kategori · Kampus</p>
                    <small id="modalEmail" style="color: #64748b; font-size: 11px;">email@domain.com</small>
                </div>
                <div style="margin-left: auto; text-align: right;">
                    <span id="modalEligibilityBadge" class="badge-eligible">Eligible</span><br>
                    <span id="modalStatusBadge" class="status status-pending" style="margin-top: 4px;">Pending</span>
                </div>
            </div>

            <!-- Detail Informasi Pendidikan Dinamis -->
            <div class="detail-grid">
                <div class="detail-item">
                    <strong>Institusi / Sekolah</strong>
                    <span id="modalInstName">-</span>
                </div>
                <div class="detail-item">
                    <strong>Jurusan / Prodi</strong>
                    <span id="modalMajorName">-</span>
                </div>
                <div class="detail-item">
                    <strong>Kota Institusi</strong>
                    <span id="modalCityName">-</span>
                </div>
                <div class="detail-item">
                    <strong>Tanggal Pengajuan</strong>
                    <span id="modalSubmitDate">-</span>
                </div>
            </div>

            <!-- Box Spesifik Kategori -->
            <div id="modalCategorySpecificBox" style="background: #f8fafc; border: 1px solid var(--line); border-radius: 8px; padding: 12px 14px; margin-bottom: 14px; font-size: 12.5px;">
                <strong style="color: var(--teal-dark); font-size: 12px; display: block; margin-bottom: 4px;">DATA AKADEMIK SPESIFIK:</strong>
                <div id="modalCategorySpecificContent" style="color: #334155;"></div>
            </div>

            <!-- Box Hasil Automatic Eligibility Screening -->
            <div style="background: #f0fdfa; border: 1px solid #99f6e4; border-radius: 8px; padding: 12px 14px; margin-bottom: 14px; font-size: 12.5px;">
                <strong style="color: #0c7566; display: flex; align-items: center; gap: 6px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                    Hasil Automatic Eligibility Screening:
                </strong>
                <p id="modalScreeningNotes" style="margin-top: 4px; color: #134e4a; line-height: 1.5; font-size: 12px;"></p>
            </div>

            <!-- Dokumen Metadata & Preview PDF Asli -->
            <div class="document-metadata" style="display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 28px; height: 28px; color: var(--teal);">
                        <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/>
                        <polyline points="14 2 14 8 20 8"/>
                    </svg>
                    <div>
                        <b id="modalDocument">KTM_Mahasiswa.pdf</b>
                        <span id="modalDocSize" style="display: block; font-size: 11px; color: #64748b;">Ukuran: -</span>
                    </div>
                </div>

                <div id="modalPdfActionArea">
                    <a id="modalPdfLink" href="#" target="_blank" class="btn btn-outline btn-sm" style="background: #fff; font-size: 12px;">
                        Buka Dokumen PDF &rarr;
                    </a>
                    <span id="modalNoPdfNotice" style="display: none; font-size: 11px; color: #94a3b8; font-style: italic;">
                        Dokumen fisik belum diunggah (data demo)
                    </span>
                </div>
            </div>

            <!-- Rejection Info (jika ditolak sebelumnya) -->
            <div id="modalPrevRejection" class="rejection-box" style="display: none; margin-top: 14px;">
                <strong>Alasan Keputusan Penolakan:</strong>
                <p id="modalPrevRejectionText" style="margin-top: 4px;"></p>
            </div>

            <!-- Form Penolakan Admin -->
            <form id="rejectForm" method="POST" style="display: none; margin-top: 14px;">
                @csrf
                <div class="form-group">
                    <label for="rejectReason">Alasan Penolakan <span style="color: #ef4444;">*</span></label>
                    <textarea id="rejectReason" name="reason" placeholder="Jelaskan alasan penolakan secara spesifik, misal: Nama pada KTM tidak sesuai dengan nama akun..." required style="min-height: 80px;"></textarea>
                </div>
                <div class="modal-actions" style="margin-top: 10px;">
                    <button type="button" class="btn btn-secondary" onclick="hideRejectForm()">Batal</button>
                    <button type="submit" class="btn btn-danger">Konfirmasi Penolakan</button>
                </div>
            </form>

            <!-- Action Buttons Footer -->
            <div id="defaultActions" class="modal-actions" style="margin-top: 20px;">
                <button type="button" class="btn btn-secondary" onclick="closeVerifyModal()">Tutup</button>
                
                <div id="pendingActionButtons" style="display: inline-flex; gap: 8px;">
                    <button type="button" class="btn btn-danger" onclick="showRejectForm()">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                        Tolak
                    </button>
                    <form id="approveForm" method="POST" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin dokumen ini valid dan menyetujui verifikasi talenta ini?');">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            Setujui
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('verifyModal');
        const rejectForm = document.getElementById('rejectForm');
        const defaultActions = document.getElementById('defaultActions');
        const pendingActionButtons = document.getElementById('pendingActionButtons');

        function openVerifyModal(data) {
            document.getElementById('modalAvatar').innerText = data.name.charAt(0).toUpperCase();
            document.getElementById('modalName').innerText = data.name;
            document.getElementById('modalEmail').innerText = data.email;
            document.getElementById('modalCategoryAndInst').innerText = `${data.category} · ${data.institution}`;
            document.getElementById('modalInstName').innerText = data.institution;
            document.getElementById('modalMajorName').innerText = data.major;
            document.getElementById('modalCityName').innerText = data.city;
            document.getElementById('modalSubmitDate').innerText = data.submitted_at;
            document.getElementById('modalScreeningNotes').innerText = data.screening_notes;

            // Kategori Spesifik
            let specHtml = '';
            if (data.category.includes('SMK')) {
                specHtml = `Kelas Saat Ini: <strong>${data.class_level || '-'}</strong> · Tahun Ajaran: <strong>${data.academic_year || '-'}</strong> · Perkiraan Lulus: <strong>${data.graduation_year || '-'}</strong>`;
            } else if (data.category.includes('Fresh')) {
                const monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                const mName = data.graduation_month ? monthNames[data.graduation_month] : '-';
                specHtml = `Bulan Kelulusan: <strong>${mName}</strong> · Tahun Kelulusan: <strong>${data.graduation_year || '-'}</strong>`;
            } else {
                specHtml = `Tahun Masuk: <strong>${data.entry_year || '-'}</strong> · Semester Saat Ini: <strong>${data.current_semester || '-'}</strong> · Tahun Akademik: <strong>${data.academic_year || '-'}</strong>`;
            }
            document.getElementById('modalCategorySpecificContent').innerHTML = specHtml;

            // Dokumen PDF
            document.getElementById('modalDocument').innerText = data.document_name;
            document.getElementById('modalDocSize').innerText = 'Ukuran: ' + (data.document_size || '-');

            const pdfLink = document.getElementById('modalPdfLink');
            const noPdfNotice = document.getElementById('modalNoPdfNotice');
            if (data.has_physical_pdf && data.pdf_url) {
                pdfLink.href = data.pdf_url;
                pdfLink.style.display = 'inline-flex';
                noPdfNotice.style.display = 'none';
            } else {
                pdfLink.style.display = 'none';
                noPdfNotice.style.display = 'inline';
            }

            // Status Badges
            const eBadge = document.getElementById('modalEligibilityBadge');
            eBadge.innerText = data.eligibility_label;
            eBadge.className = data.eligibility_status === 'eligible' ? 'badge-eligible' : (data.eligibility_status === 'ineligible' ? 'badge-ineligible' : 'badge-undetermined');

            const vBadge = document.getElementById('modalStatusBadge');
            vBadge.innerText = data.verification_label;
            vBadge.className = 'status ' + (
                (data.verification_status === 'verified' || data.verification_status === 'Terverifikasi') ? 'status-verified' :
                ((data.verification_status === 'rejected' || data.verification_status === 'Ditolak' || data.verification_status === 'expired') ? 'status-rejected' : 'status-pending')
            );

            // Rejection reason display
            const prevRejectionBox = document.getElementById('modalPrevRejection');
            if (data.rejection_reason) {
                document.getElementById('modalPrevRejectionText').innerText = data.rejection_reason;
                prevRejectionBox.style.display = 'block';
            } else {
                prevRejectionBox.style.display = 'none';
            }

            // Setup Approve & Reject URLs
            document.getElementById('approveForm').action = data.approve_url;
            rejectForm.action = data.reject_url;

            // Action buttons visibility
            if (data.verification_status === 'pending' || data.verification_status === 'Menunggu Verifikasi') {
                pendingActionButtons.style.display = 'inline-flex';
            } else {
                pendingActionButtons.style.display = 'none';
            }

            // Reset form view
            hideRejectForm();

            // Show modal
            modal.classList.add('show');
        }

        function closeVerifyModal() {
            modal.classList.remove('show');
            hideRejectForm();
        }

        function showRejectForm() {
            rejectForm.style.display = 'block';
            defaultActions.style.display = 'none';
            document.getElementById('rejectReason').focus();
        }

        function hideRejectForm() {
            rejectForm.style.display = 'none';
            defaultActions.style.display = 'flex';
            document.getElementById('rejectReason').value = '';
        }

        // Close on backdrop click
        window.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeVerifyModal();
            }
        });

        // Close on ESC
        window.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.classList.contains('show')) {
                closeVerifyModal();
            }
        });
    </script>
</body>
</html>
