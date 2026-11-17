/**
 * Tombol "mata" untuk menampilkan/menyembunyikan kata sandi.
 * Otomatis dipasang ke setiap <input type="password"> di halaman yang memuat script ini.
 */
(function (document) {
    const EYE = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>';
    const EYE_OFF = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9.9 4.24A9.1 9.1 0 0 1 12 4c6.5 0 10 7 10 7a17.6 17.6 0 0 1-2.16 3.19M6.6 6.6A17.4 17.4 0 0 0 2 12s3.5 7 10 7a9.7 9.7 0 0 0 5.4-1.6"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="m2 2 20 20"/></svg>';

    function setVisible(input, button, visible) {
        input.type = visible ? 'text' : 'password';
        button.innerHTML = visible ? EYE_OFF : EYE;
        button.setAttribute('aria-pressed', String(visible));
        button.setAttribute('aria-label', visible ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
        button.title = visible ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi';
    }

    function enhance(input) {
        if (input.dataset.pwToggle === 'on') return;
        input.dataset.pwToggle = 'on';

        const wrap = document.createElement('span');
        wrap.className = 'pw-wrap';
        input.parentNode.insertBefore(wrap, input);
        wrap.appendChild(input);

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'pw-toggle';
        if (input.id) button.setAttribute('aria-controls', input.id);
        wrap.appendChild(button);
        setVisible(input, button, false);

        button.addEventListener('click', function () {
            const start = input.selectionStart;
            const end = input.selectionEnd;
            setVisible(input, button, input.type === 'password');
            input.focus();
            try { input.setSelectionRange(start, end); } catch (e) { /* abaikan */ }
        });

        // Kembalikan ke mode tersembunyi saat form dikirim agar browser tidak menyimpannya sebagai teks biasa.
        if (input.form) {
            input.form.addEventListener('submit', function () { setVisible(input, button, false); });
        }
    }

    function init() {
        document.querySelectorAll('input[type="password"]').forEach(enhance);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(document);
