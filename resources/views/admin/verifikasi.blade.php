<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Talenta - Admin Panel | TANDANG</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
    <div class="app-shell">
        <!-- Sidebar Navigation -->
        <aside class="app-sidebar">
            <a href="/" class="sidebar-logo">
                <span class="mark">▥</span>
                TANDANG
            </a>

            <div class="role-badge-tag">Admin Panel</div>

            <nav class="sidebar-nav">
                <a href="{{ route('admin.verifikasi.index') }}" class="active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        <path d="m9 12 2 2 4-4"/>
                    </svg>
                    Verifikasi Talenta
                    @if($counts['Menunggu Verifikasi'] > 0)
                        <span class="badge-counter">{{ $counts['Menunggu Verifikasi'] }}</span>
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
                    Panel Moderasi & Verifikasi
                </span>
                <span class="status status-verified">Sistem Aktif</span>
            </header>

            <main class="dash-main">
                <div class="page-head">
                    <h1>Verifikasi Talenta</h1>
                    <p>Tinjau metadata dokumen untuk memvalidasi status siswa, mahasiswa aktif, atau fresh graduate.</p>
                </div>

                <!-- Toast Alerts -->
                @if(session('success'))
                    <div class="toast-box toast-success">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('warning'))
                    <div class="toast-box toast-warning">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        {{ session('warning') }}
                    </div>
                @endif

                @if(isset($errors) && $errors->any())
                    <div class="toast-box" style="background: #fee2e2; color: #991b1b; border: 1px solid #fecaca;">
                        <ul style="padding-left: 20px;">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Management Toolbar: Filter & Search -->
                <div class="management-toolbar">
                    <div class="filter-pills">
                        @foreach(['Semua', 'Menunggu Verifikasi', 'Terverifikasi', 'Ditolak'] as $filter)
                            <a href="{{ route('admin.verifikasi.index', ['status' => $filter, 'q' => $searchQuery]) }}"
                               class="{{ $currentFilter === $filter ? 'active' : '' }}">
                                {{ $filter }}
                                <span>{{ $counts[$filter] ?? 0 }}</span>
                            </a>
                        @endforeach
                    </div>

                    <form method="GET" action="{{ route('admin.verifikasi.index') }}">
                        <input type="hidden" name="status" value="{{ $currentFilter }}">
                        <div class="searchbox">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"/>
                                <path d="m21 21-4.3-4.3"/>
                            </svg>
                            <input type="text" name="q" value="{{ $searchQuery }}" placeholder="Cari nama, email, kampus...">
                        </div>
                    </form>
                </div>

                <!-- Table Card -->
                <div class="table-card">
                    @if($talents->count() > 0)
                        <table>
                            <thead>
                                <tr>
                                    <th>Talenta</th>
                                    <th>Kategori & Institusi</th>
                                    <th>Diajukan</th>
                                    <th>Dokumen</th>
                                    <th>Status</th>
                                    <th style="text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($talents as $talent)
                                    @php
                                        $statusClass = match($talent->verification_status) {
                                            'Terverifikasi' => 'status-verified',
                                            'Ditolak' => 'status-rejected',
                                            default => 'status-pending',
                                        };
                                    @endphp
                                    <tr>
                                        <td>
                                            <b>{{ $talent->user->name ?? 'Pengguna' }}</b>
                                            <small>{{ $talent->user->email ?? '—' }}</small>
                                        </td>
                                        <td>
                                            <b>{{ $talent->category }}</b>
                                            <small>{{ $talent->institution ?? 'Institusi belum diisi' }} {{ $talent->major ? '· ' . $talent->major : '' }}</small>
                                        </td>
                                        <td>
                                            {{ $talent->submitted_at ? $talent->submitted_at->locale('id')->isoFormat('D MMM Y') : '—' }}
                                        </td>
                                        <td>
                                            <span style="font-size: 12px; color: #475569;">
                                                {{ $talent->document ?? 'Belum ada berkas' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status {{ $statusClass }}">
                                                {{ $talent->verification_status }}
                                            </span>
                                        </td>
                                        <td style="text-align: center;">
                                            <button type="button" 
                                                    class="icon-action" 
                                                    title="Tinjau Pengajuan"
                                                    onclick="openVerifyModal({{ json_encode([
                                                        'id' => $talent->id,
                                                        'name' => $talent->user->name ?? 'Pengguna',
                                                        'email' => $talent->user->email ?? '',
                                                        'category' => $talent->category,
                                                        'institution' => $talent->institution ?? 'Institusi belum diisi',
                                                        'major' => $talent->major ?? '',
                                                        'city' => $talent->city ?? '',
                                                        'document' => $talent->document ?? 'Dokumen belum tersedia',
                                                        'status' => $talent->verification_status,
                                                        'rejection_reason' => $talent->rejection_reason,
                                                        'approve_url' => route('admin.verifikasi.approve', $talent->id),
                                                        'reject_url' => route('admin.verifikasi.reject', $talent->id),
                                                    ]) }})">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                                                    <circle cx="12" cy="12" r="3"/>
                                                </svg>
                                            </button>
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
                            <p style="font-weight: 700; color: #1e293b;">Tidak ada data pengajuan</p>
                            <p style="font-size: 12px; margin-top: 4px;">Tidak ditemukan talenta pada filter atau kata kunci ini.</p>
                        </div>
                    @endif
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Detail & Aksi Verifikasi -->
    <div id="verifyModal" class="modal-backdrop">
        <div class="modal-box">
            <div class="modal-header">
                <h2>Detail Verifikasi Talenta</h2>
                <button type="button" class="modal-close" onclick="closeVerifyModal()">&times;</button>
            </div>

            <div class="talent-modal-head">
                <div class="avatar" id="modalAvatar">T</div>
                <div>
                    <h3 id="modalName">Nama Talenta</h3>
                    <p id="modalCategoryAndInst">Kategori · Kampus</p>
                    <small id="modalEmail" style="color: #64748b; font-size: 11px;">email@domain.com</small>
                </div>
                <span id="modalStatusBadge" class="status status-pending">Menunggu Verifikasi</span>
            </div>

            <div class="document-metadata">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/>
                    <polyline points="14 2 14 8 20 8"/>
                </svg>
                <div>
                    <b id="modalDocument">KTM_Mahasiswa.pdf</b>
                    <span>Tinjauan dokumen identitas/status akademik pemohon.</span>
                </div>
            </div>

            <!-- Existing Rejection Reason (if rejected previously) -->
            <div id="modalPrevRejection" class="rejection-box" style="display: none;">
                <strong>Catatan Penolakan Sebelumnya:</strong>
                <p id="modalPrevRejectionText" style="margin-top: 4px;"></p>
            </div>

            <!-- Rejection Form Section (toggled by Tolak button) -->
            <form id="rejectForm" method="POST" style="display: none;">
                @csrf
                <div class="form-group">
                    <label for="rejectReason">Alasan Penolakan <span style="color: #ef4444;">*</span></label>
                    <textarea id="rejectReason" name="reason" placeholder="Contoh: Dokumen ijazah melebihi batas 2 tahun kelulusan..." required></textarea>
                </div>
                <div class="modal-actions" style="margin-top: 10px;">
                    <button type="button" class="btn btn-secondary" onclick="hideRejectForm()">Batal</button>
                    <button type="submit" class="btn btn-danger">Kirim Penolakan</button>
                </div>
            </form>

            <!-- Default Modal Actions -->
            <div id="defaultActions" class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeVerifyModal()">Tutup</button>
                
                <div id="pendingActionButtons" style="display: inline-flex; gap: 8px;">
                    <button type="button" class="btn btn-danger" onclick="showRejectForm()">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                        Tolak
                    </button>
                    <form id="approveForm" method="POST" style="display: inline;">
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
            document.getElementById('modalDocument').innerText = data.document;

            // Status Badge
            const badge = document.getElementById('modalStatusBadge');
            badge.innerText = data.status;
            badge.className = 'status ' + (
                data.status === 'Terverifikasi' ? 'status-verified' :
                data.status === 'Ditolak' ? 'status-rejected' : 'status-pending'
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
            if (data.status === 'Menunggu Verifikasi') {
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
