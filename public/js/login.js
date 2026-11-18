const loginForm = document.getElementById('loginForm');
const message = document.getElementById('message');
const forgotLink = document.getElementById('forgotLink');

if (loginForm && message) {
    loginForm.addEventListener('submit', async function (event) {
        event.preventDefault();

        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const rememberInput = document.getElementById('remember');
        const submitBtn = loginForm.querySelector('button[type="submit"]');

        const email = emailInput ? emailInput.value.trim() : '';
        const password = passwordInput ? passwordInput.value : '';
        const remember = rememberInput ? rememberInput.checked : false;

        message.style.color = '#0fa48f';
        message.textContent = 'Memverifikasi kredensial...';
        if (submitBtn) submitBtn.disabled = true;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const response = await fetch('/web-login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || ''
                },
                body: JSON.stringify({ email, password, remember })
            });

            const data = await response.json();

            if (!response.ok) {
                if (data.reason === 'account_inactive' && data.support_email) {
                    showInactiveMessage(data.support_email, email);
                    if (submitBtn) submitBtn.disabled = false;
                    return;
                }
                throw new Error(data.message || 'Email atau kata sandi tidak sesuai.');
            }

            message.style.color = '#0fa48f';
            message.textContent = data.message || 'Login berhasil. Mengalihkan...';

            if (data.redirect) {
                window.location.href = data.redirect;
            }
        } catch (error) {
            message.style.color = '#dc2626';
            message.textContent = error.message || 'Terjadi kesalahan saat masuk. Silakan coba lagi.';
            if (submitBtn) submitBtn.disabled = false;
        }
    });
}

// Akun dinonaktifkan Admin: tampilkan email bantuan yang bisa diklik (mailto dengan subjek & email akun terisi).
// Dibangun lewat DOM (bukan innerHTML) agar nilai dari server/input tidak dieksekusi sebagai HTML.
function showInactiveMessage(supportEmail, accountEmail) {
    const subject = 'Pengajuan Aktivasi Kembali Akun TANDANG';
    const body = 'Halo Tim TANDANG,\n\nAkun saya dengan email ' + (accountEmail || '-') +
        ' dinonaktifkan. Mohon informasi lebih lanjut / pengajuan aktivasi kembali.\n\nTerima kasih.';

    const link = document.createElement('a');
    link.href = 'mailto:' + supportEmail + '?subject=' + encodeURIComponent(subject) + '&body=' + encodeURIComponent(body);
    link.textContent = supportEmail;
    link.style.color = '#0f766e';
    link.style.fontWeight = '700';
    link.style.textDecoration = 'underline';

    message.style.color = '#dc2626';
    message.replaceChildren(
        document.createTextNode('Akun Anda dinonaktifkan oleh Administrator. Untuk informasi lebih lanjut atau pengajuan aktivasi kembali, hubungi kami di '),
        link,
        document.createTextNode('.')
    );
}

if (forgotLink) {
    forgotLink.addEventListener('click', function (event) {
        event.preventDefault();
        if (typeof window.tandangAlert === 'function') {
            window.tandangAlert({
                title: 'Lupa Kata Sandi',
                message: 'Fitur pemulihan kata sandi otomatis akan segera hadir. Silakan hubungi Administrator jika Anda mengalami kendala saat masuk ke sistem.',
                type: 'info',
                buttonText: 'Mengerti'
            });
        } else {
            alert('Fitur lupa kata sandi akan segera hadir.');
        }
    });
}
