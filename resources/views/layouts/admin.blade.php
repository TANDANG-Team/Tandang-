<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Panel') - Admin Panel | TANDANG</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="stylesheet" href="{{ asset('css/tandang-features.css') }}?v=2">
    @stack('styles')
</head>
<body>
    {{-- Layout ini mengikuti struktur halaman admin yang sudah ada (verifikasi/pengguna/proyek). --}}
    <div class="app-shell">
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

                <a href="{{ route('admin.proyek.index') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="20" height="14" x="2" y="7" rx="2" ry="2"/>
                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                    </svg>
                    Kelola Proyek
                </a>

                @include('admin.partials.promo-nav-link')

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
                    <small>{{ Auth::user()->email ?? '' }}</small>
                </div>
                <form action="{{ route('logout') }}" method="POST" style="margin-left: auto;">
                    @csrf
                    <button type="submit" title="Keluar / Logout" style="border: 0; background: transparent; color: #94a3b8; cursor: pointer; display: flex; align-items: center; padding: 6px; border-radius: 6px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                    </button>
                </form>
            </div>
        </aside>

        <div class="dash-container">
            <header class="dash-header">
                <span style="font-size: 13px; font-weight: 700; color: #64748b;">@yield('header', 'Panel Admin')</span>
                <span class="status status-verified">Sistem Aktif</span>
            </header>

            <main class="dash-main">
                @if(session('success'))
                    <div class="toast-box toast-success">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        {{ session('success') }}
                    </div>
                @endif

                @if(isset($errors) && $errors->any())
                    <div class="toast-box" role="alert" style="background: #fee2e2; color: #991b1b; border: 1px solid #fecaca;">
                        <ul style="padding-left: 20px;">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
