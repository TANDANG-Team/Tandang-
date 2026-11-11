<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Ruang Penyedia') | TANDANG</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">

    <style>
        :root {
            --primary: #239b65;
            --primary-dark: #18794e;
            --primary-light: #e9f7ef;
            --primary-soft: #f3fbf6;
            --sidebar-width: 260px;
        }

        .dashboard-layout {
            display: flex;
            min-height: 100vh;
            background: #f7f8fa;
        }

        /* Sidebar styles matching Penyedia.pdf */
        .sidebar {
            width: var(--sidebar-width);
            background: #ffffff;
            border-right: 1px solid #e9edf2;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            overflow-y: auto;
        }

        .sidebar-brand {
            padding: 24px 20px 16px;
            display: flex;
            align-items: center;
        }

        .sidebar-brand img {
            height: 38px;
            width: auto;
        }

        .sidebar-space-label {
            padding: 4px 20px 12px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.2px;
            color: #82909f;
            text-transform: uppercase;
        }

        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding: 0 12px;
            flex: 1;
        }

        .nav-link-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            border-radius: 10px;
            color: #4b5563;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .nav-link-item:hover {
            background: #f3fbf6;
            color: var(--primary);
        }

        .nav-link-item.active {
            background: var(--primary-light);
            color: var(--primary);
            font-weight: 700;
        }

        .nav-link-item svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }

        .nav-badge-pill {
            margin-left: auto;
            background: #ef4444;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 999px;
        }

        /* Sidebar Footer / User Info */
        .sidebar-footer {
            padding: 16px 14px;
            border-top: 1px solid #e9edf2;
            background: #ffffff;
        }

        .user-profile-widget {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px;
            border-radius: 10px;
            background: #f9fafb;
            position: relative;
        }

        .user-avatar-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #239b65;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
            flex-shrink: 0;
            overflow: hidden;
        }

        .user-avatar-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-info-text {
            flex: 1;
            min-width: 0;
        }

        .user-display-name {
            font-size: 13.5px;
            font-weight: 700;
            color: #1f2a37;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-role-tag {
            display: inline-block;
            font-size: 11px;
            font-weight: 600;
            color: #047857;
            background: #d1fae5;
            padding: 1px 6px;
            border-radius: 4px;
        }

        .logout-inline-btn {
            background: transparent;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            padding: 6px;
            border-radius: 6px;
            transition: color 0.2s, background 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logout-inline-btn:hover {
            color: #dc2626;
            background: #fee2e2;
        }

        /* Main Content Container */
        .main-wrapper {
            margin-left: var(--sidebar-width);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            padding: 32px 36px 60px;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .main-wrapper {
                margin-left: 0;
                padding: 20px 16px;
            }
        }

        /* Page Top Bar */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            gap: 16px;
            flex-wrap: wrap;
        }

        .page-title-box h1 {
            font-size: 26px;
            font-weight: 800;
            color: #1f2a37;
            margin: 0 0 4px;
            letter-spacing: -0.3px;
        }

        .page-title-box p {
            margin: 0;
            color: #6b7280;
            font-size: 14px;
        }

        /* Common Action Button */
        .btn-primary-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--primary);
            color: #ffffff;
            font-size: 14px;
            font-weight: 600;
            padding: 10px 18px;
            border-radius: 10px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
        }

        .btn-primary-action:hover {
            background: var(--primary-dark);
            color: #ffffff;
        }

        .btn-secondary-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #ffffff;
            color: #374151;
            font-size: 14px;
            font-weight: 600;
            padding: 9px 16px;
            border-radius: 10px;
            text-decoration: none;
            border: 1.5px solid #e5e7eb;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-secondary-action:hover {
            background: #f9fafb;
            border-color: #d1d5db;
        }

        /* Flash Message Alerts */
        .alert-box {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            font-weight: 500;
        }

        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-info {
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        /* Metric Cards */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 28px;
        }

        @media (max-width: 1024px) {
            .metrics-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .metrics-grid {
                grid-template-columns: 1fr;
            }
        }

        .metric-card {
            background: #ffffff;
            border: 1px solid #e9edf2;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
        }

        .metric-number {
            font-size: 32px;
            font-weight: 800;
            color: #111827;
            line-height: 1.1;
            margin-bottom: 6px;
        }

        .metric-title {
            font-size: 13.5px;
            color: #6b7280;
            font-weight: 500;
        }

        /* Status Badges */
        .badge-status {
            display: inline-block;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
        }

        .badge-published {
            background: #ecfdf5;
            color: #065f46;
        }

        .badge-draft {
            background: #f3f4f6;
            color: #4b5563;
        }

        .badge-closed {
            background: #fef2f2;
            color: #991b1b;
        }

        .badge-archived {
            background: #fffbeb;
            color: #92400e;
        }

        .badge-menunggu {
            background: #eff6ff;
            color: #1e40af;
        }

        .badge-dalam_review {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-diterima {
            background: #ecfdf5;
            color: #065f46;
        }

        .badge-ditolak {
            background: #fef2f2;
            color: #991b1b;
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="dashboard-layout">
        <!-- ============ SIDEBAR RUANG PENYEDIA ============ -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <a href="{{ route('penyedia.dashboard') }}">
                    <img src="{{ asset('images/LogoTandang.png') }}" alt="Logo TANDANG">
                </a>
            </div>

            <div class="sidebar-space-label">RUANG PENYEDIA</div>

            <nav class="sidebar-nav">
                <a href="{{ route('penyedia.dashboard') }}" class="nav-link-item {{ request()->routeIs('penyedia.dashboard') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 10.5 12 3l9 7.5"/>
                        <path d="M5 9.5V21h5v-6h4v6h5V9.5"/>
                    </svg>
                    <span>Beranda</span>
                </a>

                <a href="{{ route('penyedia.projects.index') }}" class="nav-link-item {{ request()->routeIs('penyedia.projects.index') || request()->routeIs('penyedia.projects.edit') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                    </svg>
                    <span>Proyek Saya</span>
                </a>

                <a href="{{ route('penyedia.projects.create') }}" class="nav-link-item {{ request()->routeIs('penyedia.projects.create') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="16"/>
                        <line x1="8" y1="12" x2="16" y2="12"/>
                    </svg>
                    <span>Buat Proyek</span>
                </a>

                <a href="{{ route('penyedia.applicants.index') }}" class="nav-link-item {{ request()->routeIs('penyedia.applicants.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    <span>Pelamar</span>
                </a>

                <a href="{{ route('penyedia.profile') }}" class="nav-link-item {{ request()->routeIs('penyedia.profile*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                    <span>Profil Usaha</span>
                </a>

                <a href="{{ route('penyedia.notifications.index') }}" class="nav-link-item {{ request()->routeIs('penyedia.notifications.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                    <span>Notifikasi</span>
                    @php
                        $unreadBadgeCount = \App\Models\ProviderNotification::where('user_id', auth()->id())->where('is_read', false)->count();
                    @endphp
                    @if($unreadBadgeCount > 0)
                        <span class="nav-badge-pill">{{ $unreadBadgeCount }}</span>
                    @endif
                </a>

                <a href="{{ route('penyedia.settings') }}" class="nav-link-item {{ request()->routeIs('penyedia.settings*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                    <span>Pengaturan</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                @php
                    $org = auth()->user()->organizationProfile;
                    $initial = strtoupper(substr($org?->organization_name ?: auth()->user()->name, 0, 1));
                    $orgName = $org?->organization_name ?: auth()->user()->name;
                @endphp
                <div class="user-profile-widget">
                    <div class="user-avatar-circle">
                        @if($org && $org->logo)
                            <img src="{{ asset($org->logo) }}" alt="Logo">
                        @else
                            {{ $initial }}
                        @endif
                    </div>
                    <div class="user-info-text">
                        <div class="user-display-name" title="{{ $orgName }}">
                            {{ $orgName }}
                        </div>
                        <span class="user-role-tag user-profile-role-badge">Penyedia Proyek</span>
                    </div>
                </div>

                <div class="user-dropdown-menu" style="margin-top: 10px; display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                    <a href="{{ route('penyedia.profile') }}" style="font-size: 12px; color: #4b5563; text-decoration: none; font-weight: 600;">
                        Profil Saya
                    </a>
                    <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                        @csrf
                        <button type="submit" class="logout-inline-btn" title="Keluar" style="font-size: 12px; display: flex; align-items: center; gap: 4px; color: #dc2626;">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                <polyline points="16 17 21 12 16 7"/>
                                <line x1="21" y1="12" x2="9" y2="12"/>
                            </svg>
                            <span>Keluar</span>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- ============ MAIN CONTENT ============ -->
        <main class="main-wrapper">
            @if(session('success'))
                <div class="alert-box alert-success">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="alert-box alert-error">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if(session('info'))
                <div class="alert-box alert-info">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    <span>{{ session('info') }}</span>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
