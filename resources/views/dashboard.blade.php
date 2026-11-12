<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Beranda | TANDANG</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
</head>
<body>
    <div class="dashboard-layout">


        <aside class="sidebar">
            <a href="{{ route('dashboard') }}" class="brand">
                <img src="{{ asset('images/LogoTandang.png') }}"
                     alt="Logo TANDANG"
                     class="brand-logo">
            </a>

            <div class="sidebar-label">RUANG TALENTA</div>

            <nav class="sidebar-nav">
                <a href="{{ route('dashboard') }}" class="nav-item active">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h5v-6h4v6h5V9.5"/></svg>
                    </span>
                    <span>Beranda</span>
                </a>

                <a href="#" class="nav-item">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9z"/><path d="M19 15l.9 2.1L22 18l-2.1.9L19 21l-.9-2.1L16 18l2.1-.9z"/></svg>
                    </span>
                    <span>Rekomendasi Proyek</span>
                </a>

                <a href="#" class="nav-item">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.35-4.35"/></svg>
                    </span>
                    <span>Cari Proyek</span>
                </a>

                <a href="#" class="nav-item">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/></svg>
                    </span>
                    <span>Lamaran Saya</span>
                </a>

                <a href="#" class="nav-item">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6.5 8-6.5s8 2.5 8 6.5"/></svg>
                    </span>
                    <span>Profil &amp; Portofolio</span>
                </a>

                <a href="#" class="nav-item">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 1 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10.3 21a2 2 0 0 0 3.4 0"/></svg>
                    </span>
                    <span>Notifikasi</span>
                    <span class="nav-badge">3</span>
                </a>
            </nav>

            <div class="sidebar-bottom">
                <div class="help-card">
                    <div class="help-icon">?</div>
                    <div>
                        <strong>Butuh bantuan?</strong>
                        <p>Temukan jawaban untuk pertanyaanmu.</p>
                    </div>
                </div>

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="logout-button">
                        <span class="nav-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>
                        </span>
                        <span>Keluar</span>
                    </button>
                </form>
            </div>
        </aside>


        <main class="main-content">

            <header class="topbar">
                <div class="topbar-search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.35-4.35"/></svg>
                    <input type="search" placeholder="Cari di TANDANG..." aria-label="Cari di TANDANG">
                </div>

                <div class="topbar-actions">
                    <button type="button" class="topbar-bell" aria-label="Notifikasi">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 1 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10.3 21a2 2 0 0 0 3.4 0"/></svg>
                        <span class="bell-dot"></span>
                    </button>

                    <div class="topbar-user-dropdown" id="userProfileDropdown">
                        <button type="button" class="user-profile-btn" id="userProfileToggle" aria-expanded="false" aria-haspopup="true">
                            <div class="user-avatar-circle">
                                <span>{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                            </div>
                            <div class="user-profile-info">
                                <span class="user-profile-name" title="{{ $user->name }}">{{ $user->name }}</span>
                                <span class="user-profile-role-badge">
                                    {{ $user->role === 'penyedia_proyek' ? 'Penyedia Proyek' : 'Talenta Muda' }}
                                </span>
                            </div>
                            <svg class="chevron-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>

                        <div class="user-dropdown-menu" id="userDropdownMenu" role="menu">
                            <div class="dropdown-header">
                                <div class="dropdown-header-avatar">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div class="dropdown-header-details">
                                    <strong class="dropdown-user-name" title="{{ $user->name }}">{{ $user->name }}</strong>
                                    <span class="dropdown-user-email" title="{{ $user->email }}">{{ $user->email }}</span>
                                </div>
                            </div>

                            <div class="dropdown-divider"></div>

                            @if ($user->role === 'penyedia_proyek')
                                <a href="{{ route('penyedia.profile') }}" class="dropdown-item" role="menuitem">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                                    </svg>
                                    <span>Profil Organisasi</span>
                                </a>
                            @else
                                <a href="{{ route('dashboard') }}" class="dropdown-item" role="menuitem">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h5v-6h4v6h5V9.5"/>
                                    </svg>
                                    <span>Beranda</span>
                                </a>
                            @endif

                            <div class="dropdown-divider"></div>

                            <form action="{{ route('logout') }}" method="POST" class="dropdown-logout-form">
                                @csrf
                                <button type="submit" class="dropdown-item dropdown-logout-btn" role="menuitem">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                        <path d="m16 17 5-5-5-5"/>
                                        <path d="M21 12H9"/>
                                    </svg>
                                    <span>Keluar</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <section class="page-content">

                @php
                    $firstName = collect(explode(' ', trim($user->name)))->first();
                    $isVerified = ! is_null($user->email_verified_at);
                @endphp

                <section class="welcome">
                    <span class="eyebrow">SELAMAT DATANG KEMBALI</span>
                    <h1>Halo, {{ $firstName }}! Siap berkarya hari ini?</h1>
                    <p>Ada beberapa proyek baru yang cocok dengan profilmu.</p>
                </section>


                <section class="cards-grid">

                    <article class="action-card profile-card">
                        <div class="profile-head">
                            <span class="card-label">PROFILMU</span>
                            <span class="profile-percent">75%</span>
                        </div>
                        <h3>Lengkapi agar makin dilirik</h3>

                        <div class="progress-track" role="progressbar" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100" aria-label="Kelengkapan profil">
                            <span class="progress-fill" style="width: 75%;"></span>
                        </div>

                        <p class="card-description">
                            Tambahkan 1 karya portofolio untuk meningkatkan peluangmu.
                        </p>

                        <a href="#" class="outline-button">Lengkapi Profil</a>
                    </article>

                    <article class="action-card verify-card">
                        <div class="verify-head">
                            <span class="verify-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m20 6-11 11-5-5"/></svg>
                            </span>
                            <span class="status-pill">Status</span>
                        </div>
                        <h3>{{ $isVerified ? 'Terverifikasi' : 'Belum terverifikasi' }}</h3>
                        <p class="card-description">
                            {{ $isVerified
                                ? 'Identitas dan statusmu telah diperiksa tim TANDANG.'
                                : 'Verifikasi emailmu untuk mengaktifkan status akun.' }}
                        </p>
                    </article>

                    <article class="action-card assessment-card">
                        <span class="assessment-spark">✦</span>
                        <h3>Kenali gaya kerjamu</h3>
                        <p class="card-description">
                            Jawab 8 pertanyaan ringan agar rekomendasi makin tepat.
                        </p>
                        <span class="assessment-note">Ini bukan tes psikologi resmi.</span>
                        <a href="#" class="assessment-button">Mulai</a>
                    </article>
                </section>


                <section class="section-block" id="rekomendasi">
                    <span class="eyebrow">REKOMENDASI UNTUKMU</span>
                    <div class="recommendation-heading">
                        <h2>Proyek dengan kecocokan terbaik</h2>
                        <a href="#" class="see-all-link">Lihat semua <span class="chev">›</span></a>
                    </div>

                    <div class="project-grid">
                        <article class="project-card">
                            <div class="project-card-top">
                                <div class="company-avatar company-green">D</div>
                                <span class="match-badge">92% Cocok</span>
                            </div>

                            <span class="project-category">Desain &amp; Kreatif</span>
                            <span class="project-company">Penyedia Proyek</span>
                            <h3>Desain Konten Media Sosial</h3>

                            <div class="project-meta">
                                <span>⌖ Remote</span>
                                <span>◷ Paruh waktu</span>
                            </div>

                            <div class="skill-list">
                                <span>Canva</span>
                                <span>Desain Grafis</span>
                            </div>

                            <div class="project-card-footer">
                                <span class="project-note">Cocok untuk portofolio</span>
                                <a href="#" class="project-link" aria-label="Lihat detail proyek">→</a>
                            </div>
                        </article>

                        <article class="project-card">
                            <div class="project-card-top">
                                <div class="company-avatar company-blue">T</div>
                                <span class="match-badge">86% Cocok</span>
                            </div>

                            <span class="project-category">Teknologi</span>
                            <span class="project-company">Penyedia Proyek</span>
                            <h3>Pengembangan Website</h3>

                            <div class="project-meta">
                                <span>⌖ Remote</span>
                                <span>◷ Berbasis proyek</span>
                            </div>

                            <div class="skill-list">
                                <span>HTML/CSS</span>
                                <span>Laravel</span>
                            </div>

                            <div class="project-card-footer">
                                <span class="project-note">Peluang mengasah skill</span>
                                <a href="#" class="project-link" aria-label="Lihat detail proyek">→</a>
                            </div>
                        </article>

                        <article class="project-card">
                            <div class="project-card-top">
                                <div class="company-avatar company-orange">M</div>
                                <span class="match-badge">81% Cocok</span>
                            </div>

                            <span class="project-category">Pemasaran</span>
                            <span class="project-company">Penyedia Proyek</span>
                            <h3>Riset Pasar Digital</h3>

                            <div class="project-meta">
                                <span>⌖ Hybrid</span>
                                <span>◷ Berbasis proyek</span>
                            </div>

                            <div class="skill-list">
                                <span>Riset</span>
                                <span>Analisis Data</span>
                            </div>

                            <div class="project-card-footer">
                                <span class="project-note">Belajar dari proyek nyata</span>
                                <a href="#" class="project-link" aria-label="Lihat detail proyek">→</a>
                            </div>
                        </article>
                    </div>

                    <p class="demo-notice">
                        Catatan: persentase kelengkapan profil dan kartu proyek di atas masih
                        berupa contoh tampilan, belum mengambil data dari database proyek.
                    </p>
                </section>

                <footer class="page-footer">
                    <span>© {{ date('Y') }} TANDANG</span>
                    <span>Ruang Tumbuh Bersama</span>
                </footer>
            </section>
        </main>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const dropdownToggle = document.getElementById('userProfileToggle');
            const dropdownContainer = document.getElementById('userProfileDropdown');

            if (dropdownToggle && dropdownContainer) {
                dropdownToggle.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const isOpen = dropdownContainer.classList.toggle('open');
                    dropdownToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                });

                document.addEventListener('click', function (e) {
                    if (!dropdownContainer.contains(e.target)) {
                        dropdownContainer.classList.remove('open');
                        dropdownToggle.setAttribute('aria-expanded', 'false');
                    }
                });

                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && dropdownContainer.classList.contains('open')) {
                        dropdownContainer.classList.remove('open');
                        dropdownToggle.setAttribute('aria-expanded', 'false');
                        dropdownToggle.focus();
                    }
                });
            }
        });
    </script>
</body>
</html>
