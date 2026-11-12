<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Proyek - Admin Panel | TANDANG</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
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

                <a href="{{ route('admin.pengguna.index') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    Kelola Pengguna
                </a>

                <a href="{{ route('admin.proyek.index') }}" class="active">
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
                    Panel Moderasi Proyek
                </span>
                <span class="status status-verified">Sistem Aktif</span>
            </header>

            <main class="dash-main">
                <div class="page-head">
                    <h1>Kelola Proyek</h1>
                    <p>Moderasi dan kelola status publikasi proyek tanpa mengubah kepemilikan atau menghapus data pelamar.</p>
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
                <form method="GET" action="{{ route('admin.proyek.index') }}" class="management-toolbar">
                    <div class="searchbox" style="flex: 1;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.3-4.3"/>
                        </svg>
                        <input type="text" name="q" value="{{ $searchQuery }}" placeholder="Cari judul proyek, kategori, lokasi..." onchange="this.form.submit()">
                    </div>

                    <select name="status" onchange="this.form.submit()">
                        <option value="Semua" {{ $statusFilter === 'Semua' ? 'selected' : '' }}>Semua Status ({{ $counts['total'] }})</option>
                        <option value="Dipublikasikan" {{ $statusFilter === 'Dipublikasikan' ? 'selected' : '' }}>Dipublikasikan ({{ $counts['published'] }})</option>
                        <option value="Draf" {{ $statusFilter === 'Draf' ? 'selected' : '' }}>Draf ({{ $counts['draft'] }})</option>
                        <option value="Ditutup" {{ $statusFilter === 'Ditutup' ? 'selected' : '' }}>Ditutup ({{ $counts['closed'] }})</option>
                        <option value="Diarsipkan" {{ $statusFilter === 'Diarsipkan' ? 'selected' : '' }}>Diarsipkan ({{ $counts['archived'] }})</option>
                    </select>

                    <select name="provider" onchange="this.form.submit()">
                        <option value="Semua">Semua Penyedia</option>
                        @foreach($providers as $provider)
                            <option value="{{ $provider->id }}" {{ $providerFilter == $provider->id ? 'selected' : '' }}>
                                {{ $provider->name }}
                            </option>
                        @endforeach
                    </select>
                </form>

                <!-- Table Card -->
                <div class="table-card">
                    @if($projects->count() > 0)
                        <table>
                            <thead>
                                <tr>
                                    <th>Proyek</th>
                                    <th>Penyedia</th>
                                    <th>Lokasi</th>
                                    <th>Pelamar</th>
                                    <th>Dibuat</th>
                                    <th>Status</th>
                                    <th style="text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($projects as $project)
                                    @php
                                        $statusClass = match($project->status) {
                                            'Dipublikasikan' => 'status-verified',
                                            'Draf' => 'status-pending',
                                            'Ditutup' => 'status-rejected',
                                            default => 'status-pending',
                                        };
                                    @endphp
                                    <tr>
                                        <td>
                                            <b>{{ $project->title }}</b>
                                            <small>{{ $project->category }} · {{ $project->compensation ?? 'Kompensasi belum diatur' }}</small>
                                        </td>
                                        <td>
                                            <b style="font-size: 12px;">{{ $project->provider_name }}</b>
                                            <small>{{ $project->user->email ?? '—' }}</small>
                                        </td>
                                        <td>
                                            {{ $project->location }}
                                            <small>{{ $project->work_type }}</small>
                                        </td>
                                        <td>
                                            <span style="font-weight: 700; color: #1e293b;">{{ $project->applicants_count }}</span>
                                            <small>pelamar</small>
                                        </td>
                                        <td>
                                            {{ $project->created_at ? $project->created_at->locale('id')->isoFormat('D MMM Y') : '—' }}
                                        </td>
                                        <td>
                                            <span class="status {{ $statusClass }}">
                                                {{ $project->status }}
                                            </span>
                                        </td>
                                        <td style="text-align: center;">
                                            <button type="button" 
                                                    class="icon-action" 
                                                    title="Tinjau & Moderasi Proyek"
                                                    onclick="openProjectModal({{ json_encode([
                                                        'id' => $project->id,
                                                        'title' => $project->title,
                                                        'category' => $project->category,
                                                        'provider' => $project->provider_name,
                                                        'location' => $project->location . ' (' . $project->work_type . ')',
                                                        'compensation' => $project->compensation ?? 'Tidak disebutkan',
                                                        'deadline' => $project->deadline ? $project->deadline->locale('id')->isoFormat('D MMMM Y') : 'Fleksibel',
                                                        'description' => $project->description,
                                                        'responsibilities' => $project->responsibilities ?? 'Tidak ada catatan tanggung jawab khusus.',
                                                        'status' => $project->status,
                                                        'applicants' => $project->applicants_count,
                                                        'update_url' => route('admin.proyek.status', $project->id),
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
                            <p style="font-weight: 700; color: #1e293b;">Tidak ada data proyek</p>
                            <p style="font-size: 12px; margin-top: 4px;">Tidak ditemukan proyek pada filter atau pencarian ini.</p>
                        </div>
                    @endif
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Moderasi Proyek -->
    <div id="projectModal" class="modal-backdrop">
        <div class="modal-box" style="max-width: 600px;">
            <div class="modal-header">
                <h2>Moderasi Proyek</h2>
                <button type="button" class="modal-close" onclick="closeProjectModal()">&times;</button>
            </div>

            <div style="margin-bottom: 16px;">
                <span id="projCategoryBadge" class="status status-verified" style="font-size: 10px; margin-bottom: 6px;">Kategori</span>
                <h3 id="projTitle" style="font-size: 18px; font-weight: 800; color: #182238; margin: 4px 0;">Judul Proyek</h3>
                <p id="projProvider" style="font-size: 12px; color: #64748b;">Penyedia</p>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; background: #f8fafc; border: 1px solid var(--line); border-radius: 12px; padding: 12px; margin-bottom: 16px; font-size: 12px;">
                <div>
                    <span style="color: #64748b; font-size: 10px; text-transform: uppercase; font-weight: 700;">Lokasi & Tipe</span>
                    <strong id="projLocation" style="display: block; color: #182238;">Remote</strong>
                </div>
                <div>
                    <span style="color: #64748b; font-size: 10px; text-transform: uppercase; font-weight: 700;">Kompensasi</span>
                    <strong id="projCompensation" style="display: block; color: #0fa48f;">Rp 1.500.000</strong>
                </div>
                <div>
                    <span style="color: #64748b; font-size: 10px; text-transform: uppercase; font-weight: 700;">Batas Waktu</span>
                    <strong id="projDeadline" style="display: block; color: #182238;">30 November 2026</strong>
                </div>
                <div>
                    <span style="color: #64748b; font-size: 10px; text-transform: uppercase; font-weight: 700;">Total Pelamar</span>
                    <strong id="projApplicants" style="display: block; color: #182238;">0 Pelamar</strong>
                </div>
            </div>

            <div style="margin-bottom: 14px;">
                <h4 style="font-size: 12px; font-weight: 700; color: #182238; margin-bottom: 4px;">Deskripsi Proyek:</h4>
                <p id="projDescription" style="font-size: 12px; color: #475569; line-height: 1.6; background: #ffffff; border: 1px solid var(--line); border-radius: 8px; padding: 10px; max-height: 90px; overflow-y: auto;">
                    Deskripsi lengkap proyek...
                </p>
            </div>

            <div style="margin-bottom: 20px;">
                <h4 style="font-size: 12px; font-weight: 700; color: #182238; margin-bottom: 4px;">Tanggung Jawab:</h4>
                <p id="projResponsibilities" style="font-size: 12px; color: #475569; line-height: 1.6; background: #ffffff; border: 1px solid var(--line); border-radius: 8px; padding: 10px; max-height: 70px; overflow-y: auto;">
                    Tanggung jawab proyek...
                </p>
            </div>

            <!-- Form Ubah Status Moderasi -->
            <form id="projectStatusForm" method="POST" style="border-top: 1px solid var(--line); padding-top: 16px;">
                @csrf
                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="statusSelect">Ubah Status Publikasi Proyek:</label>
                    <select id="statusSelect" name="status" style="width: 100%; height: 42px; border: 1px solid var(--line); border-radius: 10px; padding: 0 12px; font-size: 13px; font-family: inherit; outline: none; background: #fff;">
                        <option value="Dipublikasikan">Dipublikasikan (Tampil di Mading/Katalog)</option>
                        <option value="Draf">Draf (Disembunyikan dari Publik)</option>
                        <option value="Ditutup">Ditutup (Tidak Menerima Pelamar Baru)</option>
                        <option value="Diarsipkan">Diarsipkan (Selesai/Arsip)</option>
                    </select>
                </div>

                <div class="modal-actions" style="margin-top: 0; padding-top: 0; border-top: none;">
                    <button type="button" class="btn btn-secondary" onclick="closeProjectModal()">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan Status</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const projectModal = document.getElementById('projectModal');

        function openProjectModal(data) {
            document.getElementById('projCategoryBadge').innerText = data.category;
            document.getElementById('projTitle').innerText = data.title;
            document.getElementById('projProvider').innerText = `Oleh: ${data.provider}`;
            document.getElementById('projLocation').innerText = data.location;
            document.getElementById('projCompensation').innerText = data.compensation;
            document.getElementById('projDeadline').innerText = data.deadline;
            document.getElementById('projApplicants').innerText = `${data.applicants} Pelamar`;
            document.getElementById('projDescription').innerText = data.description;
            document.getElementById('projResponsibilities').innerText = data.responsibilities;

            // Set current status in select dropdown
            document.getElementById('statusSelect').value = data.status;
            document.getElementById('projectStatusForm').action = data.update_url;

            projectModal.classList.add('show');
        }

        function closeProjectModal() {
            projectModal.classList.remove('show');
        }

        // Close on backdrop click
        window.addEventListener('click', function(e) {
            if (e.target === projectModal) closeProjectModal();
        });

        // Close on ESC
        window.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeProjectModal();
        });
    </script>
</body>
</html>

