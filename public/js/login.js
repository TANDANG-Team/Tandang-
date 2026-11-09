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

if (forgotLink) {
    forgotLink.addEventListener('click', function (event) {
        event.preventDefault();
        alert('Fitur lupa kata sandi akan segera hadir.');
    });
}
