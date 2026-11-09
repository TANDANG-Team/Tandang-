<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk | TANDANG</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <script src="{{ asset('js/login.js') }}" defer></script>
</head>
<body>
    <main class="container">
        <section class="welcome">
            <div class="logo"><span>▥</span> TANDANG</div>
            <div class="intro">
                <div class="eyebrow">Ruang tumbuh bersama</div>
                <h1>Selamat datang<br>kembali.</h1>
                <p>Proyek singkat yang memberi pengalaman, koneksi, dan dampak nyata untuk masa depanmu.</p>
            </div>
            <div class="circle"></div>
        </section>

        <section class="form-side">
            <a class="back" href="/">Kembali ke beranda</a>

            <div class="form-content">
                <div class="eyebrow">Masuk ke akun</div>
                <h2>Senang bertemu lagi!</h2>
                <p class="subtitle">Masukkan detail akunmu untuk melanjutkan.</p>

                @if(session('error'))
                    <div style="background:#fee2e2;color:#991b1b;padding:10px 14px;border-radius:8px;font-size:12px;margin-bottom:14px;border:1px solid #fecaca;">
                        {{ session('error') }}
                    </div>
                @endif

                @if(session('warning'))
                    <div style="background:#fef3c7;color:#92400e;padding:10px 14px;border-radius:8px;font-size:12px;margin-bottom:14px;border:1px solid #fde68a;">
                        {{ session('warning') }}
                    </div>
                @endif

                @if(session('success'))
                    <div style="background:#d1fae5;color:#065f46;padding:10px 14px;border-radius:8px;font-size:12px;margin-bottom:14px;border:1px solid #a7f3d0;">
                        {{ session('success') }}
                    </div>
                @endif

                <form id="loginForm">
                    <label for="email">Email</label>
                    <input id="email" type="email" placeholder="nama@email.com" required>

                    <label for="password">Kata sandi</label>
                    <input id="password" type="password" placeholder="Minimal 8 karakter" required>

                    <div class="options">
                        <div class="remember">
                            <input type="checkbox" id="remember">
                            <label for="remember">Ingat saya</label>
                        </div>
                        <a href="#" id="forgotLink">Lupa kata sandi?</a>
                    </div>

                    <button class="submit" type="submit">Masuk&nbsp; ›</button>
                    <p id="message" role="status" style="font-size:13px;color:#c24141;"></p>
                </form>

                <div class="register">Belum punya akun? <a href="/register">Daftar sekarang</a></div>
            </div>
        </section>
    </main>
</body>
</html>
