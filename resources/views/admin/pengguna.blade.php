<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - Admin Panel | TANDANG</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
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
                <a href="{{ route('admin.verifikasi.index') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        <path d="m9 12 2 2 4-4"/>
                    </svg>
                    Verifikasi Talenta
                </a>

                <a href="{{ route('admin.pengguna.index') }}" class="active">
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
                    Panel Moderasi & Pengguna
                </span>
                <span class="status status-verified">Sistem Aktif</span>
            </header>

            <main class="dash-main">
                <div class="page-head">
                    <h1>Kelola Pengguna</h1>
                    <p>Kelola informasi umum dan status akses akun tanpa membuka kata sandi pengguna.</p>
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

                <!-- Management Toolbar: Search & Select Filters -->
                <form method="GET" action="{{ route('admin.pengguna.index') }}" class="management-toolbar">
                    <div class="searchbox" style="flex: 1;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.3-4.3"/>
                        </svg>
                        <input type="text" name="q" value="{{ $searchQuery }}" placeholder="Cari nama atau email..." onchange="this.form.submit()">
                    </div>

                    <select name="role" onchange="this.form.submit()">
                        <option value="Semua" {{ $roleFilter === 'Semua' ? 'selected' : '' }}>Semua Peran</option>
                        <option value="talenta_muda" {{ $roleFilter === 'talenta_muda' ? 'selected' : '' }}>Talenta ({{ $counts['talent'] }})</option>
                        <option value="penyedia_proyek" {{ $roleFilter === 'penyedia_proyek' ? 'selected' : '' }}>Penyedia ({{ $counts['provider'] }})</option>
                        <option value="admin" {{ $roleFilter === 'admin' ? 'selected' : '' }}>Admin ({{ $counts['admin'] }})</option>
                    </select>

                    <select name="status" onchange="this.form.submit()">
                        <option value="Semua" {{ $statusFilter === 'Semua' ? 'selected' : '' }}>Semua Status</option>
                        <option value="Aktif" {{ $statusFilter === 'Aktif' ? 'selected' : '' }}>Aktif ({{ $counts['active'] }})</option>
                        <option value="Dinonaktifkan" {{ $statusFilter === 'Dinonaktifkan' ? 'selected' : '' }}>Dinonaktifkan ({{ $counts['inactive'] }})</option>
                    </select>
                </form>

                <!-- Table Card -->
                <div class="table-card">
                    @if($users->count() > 0)
                        <table>
                            <thead>
                                <tr>
                                    <th>Pengguna</th>
                                    <th>Peran</th>
                                    <th>Verifikasi</th>
                                    <th>Terdaftar</th>
                                    <th>Status</th>
                                    <th style="text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $user)
                                    @php
                                        $roleLabel = match($user->role) {
                                            'admin' => 'Admin',
                                            'penyedia_proyek' => 'Penyedia',
                                            default => 'Talenta',
                                        };
                                        $verificationStatus = $user->talentProfile->verification_status ?? '—';
                                        $verClass = match($verificationStatus) {
                                            'Terverifikasi' => 'status-verified',
                                            'Ditolak' => 'status-rejected',
                                            'Menunggu Verifikasi' => 'status-pending',
                                            default => '',
                                        };
                                    @endphp
                                    <tr>
                                        <td>
                                            <b>{{ $user->name }}</b>
                                            <small>{{ $user->email }}</small>
                                        </td>
                                        <td>
                                            <span style="font-weight: 700; color: #334155;">{{ $roleLabel }}</span>
                                        </td>
                                        <td>
                                            @if($verificationStatus !== '—')
                                                <span class="status {{ $verClass }}">{{ $verificationStatus }}</span>
                                            @else
                                                <span style="color: #94a3b8;">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $user->created_at ? $user->created_at->locale('id')->isoFormat('D MMM Y') : '—' }}
                                        </td>
                                        <td>
                                            <span class="status {{ $user->is_active ? 'status-verified' : 'status-rejected' }}">
                                                {{ $user->is_active ? 'Aktif' : 'Dinonaktifkan' }}
                                            </span>
                                        </td>
                                        <td style="text-align: center;">
                                            <div class="row-actions">
                                                <!-- View Detail Button -->
                                                <button type="button" 
                                                        title="Lihat detail"
                                                        onclick="openUserDetailModal({{ json_encode([
                                                            'name' => $user->name,
                                                            'email' => $user->email,
                                                            'role' => $roleLabel,
                                                            'is_active' => $user->is_active,
                                                            'created_at' => $user->created_at ? $user->created_at->locale('id')->isoFormat('D MMMM Y, HH:mm') : '—',
                                                            'institution' => $user->talentProfile->institution ?? '—',
                                                            'category' => $user->talentProfile->category ?? '—',
                                                        ]) }})">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                                                        <circle cx="12" cy="12" r="3"/>
                                                    </svg>
                                                </button>

                                                <!-- Edit Button -->
                                                <button type="button" 
                                                        title="Edit informasi"
                                                        onclick="openEditUserModal({{ json_encode([
                                                            'id' => $user->id,
                                                            'name' => $user->name,
                                                            'email' => $user->email,
                                                            'update_url' => route('admin.pengguna.update', $user->id),
                                                        ]) }})">
                                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                                        <path d="m15 5 4 4"/>
                                                    </svg>
                                                </button>

                                                <!-- Toggle Active State (Lock/Unlock) -->
                                                @if($user->id !== Auth::id())
                                                    <form action="{{ route('admin.pengguna.toggle', $user->id) }}" method="POST" style="display:inline;">
                                                        @csrf
                                                        <button type="submit" 
                                                                class="{{ $user->is_active ? 'danger-link' : '' }}"
                                                                title="{{ $user->is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun' }}"
                                                                onclick="return confirm('Apakah Anda yakin ingin {{ $user->is_active ? 'menonaktifkan' : 'mengaktifkan' }} akun {{ $user->name }}?')">
                                                            @if($user->is_active)
                                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                                                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                                                </svg>
                                                            @else
                                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                    <polyline points="20 6 9 17 4 12"/>
                                                                </svg>
                                                            @endif
                                                        </button>
                                                    </form>
                                                @endif
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
                            <p style="font-weight: 700; color: #1e293b;">Tidak ada data pengguna</p>
                            <p style="font-size: 12px; margin-top: 4px;">Tidak ditemukan pengguna pada filter atau pencarian ini.</p>
                        </div>
                    @endif
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Detail Pengguna -->
    <div id="userDetailModal" class="modal-backdrop">
        <div class="modal-box">
            <div class="modal-header">
                <h2>Detail Pengguna</h2>
                <button type="button" class="modal-close" onclick="closeUserDetailModal()">&times;</button>
            </div>

            <div class="talent-modal-head">
                <div class="avatar" id="detailAvatar">U</div>
                <div>
                    <h3 id="detailName">Nama Pengguna</h3>
                    <p id="detailRole">Peran</p>
                    <small id="detailEmail" style="color: #64748b; font-size: 11px;">email@domain.com</small>
                </div>
                <span id="detailStatusBadge" class="status status-verified">Aktif</span>
            </div>

            <div class="document-metadata" style="margin-top: 10px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <div>
                    <b id="detailCreatedAt">Tanggal Terdaftar</b>
                    <span>Waktu pertama kali akun dibuat di platform.</span>
                </div>
            </div>

            <p style="font-size: 11px; color: #94a3b8; line-height: 1.5; margin-top: 14px;">
                Admin tidak dapat melihat atau mengubah kata sandi pengguna secara langsung demi privasi dan keamanan akun.
            </p>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeUserDetailModal()">Tutup</button>
            </div>
        </div>
    </div>

    <!-- Modal Edit Pengguna -->
    <div id="editUserModal" class="modal-backdrop">
        <div class="modal-box">
            <div class="modal-header">
                <h2>Edit Informasi Pengguna</h2>
                <button type="button" class="modal-close" onclick="closeEditUserModal()">&times;</button>
            </div>

            <form id="editUserForm" method="POST">
                @csrf
                <div class="form-group">
                    <label for="editName">Nama Lengkap <span style="color: #ef4444;">*</span></label>
                    <input type="text" id="editName" name="name" required style="width: 100%; height: 42px; border: 1px solid var(--line); border-radius: 10px; padding: 0 12px; font-size: 13px; font-family: inherit; outline: none;">
                </div>

                <div class="form-group">
                    <label for="editEmail">Email <span style="color: #ef4444;">*</span></label>
                    <input type="email" id="editEmail" name="email" required style="width: 100%; height: 42px; border: 1px solid var(--line); border-radius: 10px; padding: 0 12px; font-size: 13px; font-family: inherit; outline: none;">
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeEditUserModal()">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const userDetailModal = document.getElementById('userDetailModal');
        const editUserModal = document.getElementById('editUserModal');

        function openUserDetailModal(data) {
            document.getElementById('detailAvatar').innerText = data.name.charAt(0).toUpperCase();
            document.getElementById('detailName').innerText = data.name;
            document.getElementById('detailEmail').innerText = data.email;
            document.getElementById('detailRole').innerText = data.role + (data.institution !== '—' ? ` · ${data.institution}` : '');
            document.getElementById('detailCreatedAt').innerText = `Terdaftar: ${data.created_at}`;

            const badge = document.getElementById('detailStatusBadge');
            badge.innerText = data.is_active ? 'Aktif' : 'Dinonaktifkan';
            badge.className = 'status ' + (data.is_active ? 'status-verified' : 'status-rejected');

            userDetailModal.classList.add('show');
        }

        function closeUserDetailModal() {
            userDetailModal.classList.remove('show');
        }

        function openEditUserModal(data) {
            document.getElementById('editName').value = data.name;
            document.getElementById('editEmail').value = data.email;
            document.getElementById('editUserForm').action = data.update_url;

            editUserModal.classList.add('show');
        }

        function closeEditUserModal() {
            editUserModal.classList.remove('show');
        }

        // Close on backdrop click
        window.addEventListener('click', function(e) {
            if (e.target === userDetailModal) closeUserDetailModal();
            if (e.target === editUserModal) closeEditUserModal();
        });

        // Close on ESC
        window.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeUserDetailModal();
                closeEditUserModal();
            }
        });
    </script>
</body>
</html>

