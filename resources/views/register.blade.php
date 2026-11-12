<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar | TANDANG</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #17213e;
        }

        .container {
            display: grid;
            grid-template-columns: 42% 58%;
            min-height: 100vh;
        }

        .welcome {
            background: #359f8d;
            color: white;
            padding: 46px 17%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow: hidden;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            user-select: none;
            text-decoration: none;
        }

        .logo-bars {
            display: flex;
            align-items: flex-end;
            gap: 3px;
            transform: rotate(-7deg);
        }

        .logo-bar {
            width: 6px;
            border-radius: 2px;
        }

        .logo-bar.bar-1 {
            height: 18px;
            background-color: #ffffff;
        }

        .logo-bar.bar-2 {
            height: 24px;
            background-color: #ffb547;
        }

        .logo-bar.bar-3 {
            height: 14px;
            background-color: #99f6e4;
        }

        .logo-text {
            font-size: 21px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: 1.5px;
        }

        .intro {
            position: relative;
            z-index: 1;
            margin: 50px 0;
        }

        .eyebrow {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .intro h1 {
            font-size: clamp(32px, 3vw, 46px);
            font-weight: 500;
            line-height: 1.2;
            margin: 16px 0 4px;
        }

        .intro p {
            line-height: 1.8;
            font-size: 15px;
        }

        .footer {
            font-size: 12px;
            color: #e5fffa;
            position: relative;
            z-index: 1;
        }

        .circle {
            position: absolute;
            width: 300px;
            height: 300px;
            right: -140px;
            bottom: -180px;
            border: 45px solid rgba(255,255,255,.07);
            border-radius: 50%;
        }

        .form-side {
            padding: 42px clamp(28px, 11%, 130px);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .back {
            display: inline-block;
            margin-bottom: 35px;
            color: #718096;
            text-decoration: none;
            font-size: 14px;
        }

        .form-content {
            width: 100%;
            max-width: 540px;
            margin: 0 auto;
        }

        .form-content > .eyebrow { color: #64748b; }

        h2 {
            font-size: 30px;
            font-weight: 500;
            margin: 14px 0 8px;
        }

        .subtitle {
            color: #718096;
            font-size: 15px;
            line-height: 1.6;
            margin: 0 0 24px;
        }

        .roles {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 25px;
        }

        .role {
            position: relative;
            display: block;
            border: 1px solid #e0e5ed;
            border-radius: 15px;
            padding: 18px;
            cursor: pointer;
            transition: .2s;
            min-height: 145px;
        }

        .role input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .role:has(input:checked) {
            border: 1.5px solid #279f8b;
            background: #f5fcfa;
            box-shadow: 0 0 0 1px #279f8b;
        }

        .role-icon {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: #e8f7f3;
            color: #279f8b;
            font-size: 21px;
            margin-bottom: 12px;
        }

        .role strong {
            display: block;
            font-size: 14px;
            margin-bottom: 7px;
        }

        .role small {
            display: block;
            color: #718096;
            font-size: 12px;
            line-height: 1.6;
        }

        .check {
            display: none;
            position: absolute;
            right: 12px;
            top: 12px;
            background: #359f8d;
            color: white;
            border-radius: 50%;
            width: 21px;
            height: 21px;
            text-align: center;
            line-height: 21px;
            font-size: 12px;
        }

        .role:has(input:checked) .check { display: block; }

        .field { margin-bottom: 16px; }

        .field label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .field input {
            width: 100%;
            height: 47px;
            border: 1px solid #d8dee8;
            border-radius: 10px;
            padding: 0 14px;
            font-family: inherit;
            font-size: 13px;
            outline: none;
        }

        .field input:focus {
            border-color: #359f8d;
            box-shadow: 0 0 0 3px #359f8d20;
        }

        .submit {
            width: 100%;
            height: 49px;
            border: 0;
            border-radius: 10px;
            margin-top: 8px;
            background: #359f8d;
            color: white;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 6px 15px #359f8d25;
        }

        .submit:hover { background: #278b7a; }
        .submit:disabled { opacity: .65; cursor: wait; }

        .login {
            text-align: center;
            font-size: 12px;
            color: #718096;
            margin-top: 14px;
        }

        .login a {
            color: #279987;
            font-weight: 600;
            text-decoration: none;
        }

        .message {
            display: none;
            padding: 12px;
            margin: 0 0 16px;
            border-radius: 8px;
            font-size: 13px;
            line-height: 1.5;
        }

        .message.error { display: block; background: #fff0f0; color: #b42318; }
        .message.success { display: block; background: #eaf8f2; color: #18794e; }

        @media(max-width: 800px) {
            .container { grid-template-columns: 1fr; }
            .welcome { position: relative; height: auto; min-height: 250px; padding: 28px; }
            .intro { margin: 35px 0 10px; }
            .intro h1 { font-size: 32px; }
            .footer { margin-top: 12px; }
            .form-side { padding: 35px 24px; }
            .back { margin-bottom: 35px; }
        }

        @media(max-width: 420px) {
            .roles { grid-template-columns: 1fr; }
            .role { min-height: 120px; }
        }
    </style>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
</head>
<body>
    <main class="container">
        <section class="welcome">
            <a href="/" class="logo">
                <div class="logo-bars">
                    <span class="logo-bar bar-1"></span>
                    <span class="logo-bar bar-2"></span>
                    <span class="logo-bar bar-3"></span>
                </div>
                <span class="logo-text">TANDANG</span>
            </a>

            <div class="intro">
                <div class="eyebrow">Ruang tumbuh bersama</div>
                <h1>Langkah kecil menuju karya nyata.</h1>
                <p>Proyek singkat yang memberi pengalaman, koneksi, dan dampak nyata untuk masa depanmu.</p>
            </div>

            <div class="footer">SDG 8 · Pekerjaan Layak &amp; Pertumbuhan Ekonomi</div>
            <div class="circle"></div>
        </section>

        <section class="form-side">
            <a class="back" href="/"> Kembali ke beranda</a>

            <div class="form-content">
                <div class="eyebrow">Buat akun baru</div>
                <h2>Kamu ingin bergabung sebagai?</h2>
                <p class="subtitle">Pilih peran yang paling sesuai. Kamu dapat melengkapinya nanti.</p>

                <div id="message" class="message" role="status"></div>

                <form id="registerForm">
                    <div class="roles">
                        <label class="role">
                            <input type="radio" name="role" value="talenta_muda" checked>
                            <span class="check">✓</span>
                            <span class="role-icon">♙</span>
                            <strong>Talenta Muda</strong>
                            <small>SMK, mahasiswa, atau fresh graduate</small>
                        </label>

                        <label class="role">
                            <input type="radio" name="role" value="penyedia_proyek">
                            <span class="check">✓</span>
                            <span class="role-icon">▣</span>
                            <strong>Penyedia Proyek</strong>
                            <small>UMKM atau pemilik usaha</small>
                        </label>
                    </div>

                    <div class="field">
                        <label for="name">Nama lengkap</label>
                        <input id="name" name="name" type="text" placeholder="Contoh: Nara Putri" required autocomplete="name">
                    </div>

                    <div id="talentFields">
                        <div class="field">
                            <label for="institution">Instansi / Kampus / Sekolah</label>
                            <input id="institution" name="institution" type="text" placeholder="Contoh: Universitas Indonesia, SMKN 1 Cimahi">
                        </div>

                        <div class="field">
                            <label for="major">Jurusan / Program Studi</label>
                            <input id="major" name="major" type="text" placeholder="Contoh: Ilmu Komputer, Rekayasa Perangkat Lunak">
                        </div>
                    </div>

                    <div class="field">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" placeholder="nama@email.com" required autocomplete="email">
                    </div>

                    <div class="field">
                        <label for="password">Kata sandi</label>
                        <input id="password" name="password" type="password" placeholder="Minimal 8 karakter" minlength="8" required autocomplete="new-password">
                    </div>

                    <div class="field">
                        <label for="password_confirmation">Konfirmasi kata sandi</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" placeholder="Ulangi kata sandi" minlength="8" required autocomplete="new-password">
                    </div>

                    <button class="submit" type="submit">Buat Akun&nbsp; ›</button>
                </form>

                <div class="login">Sudah punya akun? <a href="/login">Masuk</a></div>
            </div>
        </section>
    </main>

    <script>
        const form = document.getElementById('registerForm');
        const message = document.getElementById('message');
        const submitButton = form.querySelector('button[type="submit"]');
        const talentFields = document.getElementById('talentFields');

        // Toggle input khusus talenta muda
        form.querySelectorAll('input[name="role"]').forEach(radio => {
            radio.addEventListener('change', function () {
                if (this.value === 'talenta_muda') {
                    talentFields.style.display = 'block';
                } else {
                    talentFields.style.display = 'none';
                }
            });
        });

        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            message.className = 'message';
            message.textContent = '';

            const password = form.password.value;
            const confirmation = form.password_confirmation.value;

            if (password !== confirmation) {
                message.textContent = 'Konfirmasi kata sandi tidak sama.';
                message.className = 'message error';
                return;
            }

            submitButton.disabled = true;
            submitButton.textContent = 'Memproses...';

            const role = form.querySelector('input[name="role"]:checked').value;
            const payload = {
                name: form.name.value.trim(),
                email: form.email.value.trim(),
                password: password,
                password_confirmation: confirmation,
                role: role
            };

            if (role === 'talenta_muda') {
                payload.institution = form.institution.value.trim();
                payload.major = form.major.value.trim();
            }

            try {
                const response = await fetch('/api/register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (!response.ok) {
                    const errors = data.errors
                        ? Object.values(data.errors).flat().join(' ')
                        : (data.message || 'Registrasi gagal. Silakan coba lagi.');

                    throw new Error(errors);
                }

                message.textContent = 'Akun berhasil dibuat! Silakan masuk menggunakan akunmu.';
                message.className = 'message success';
                form.reset();
                window.scrollTo({ top: 0, behavior: 'smooth' });

                setTimeout(() => {
                    window.location.href = '/login';
                }, 1500);

            } catch (error) {
                message.textContent = error.message || 'Tidak dapat terhubung ke server.';
                message.className = 'message error';
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = 'Buat Akun ›';
            }
        });
    </script>
</body>
</html>
