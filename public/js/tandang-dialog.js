/**
 * TANDANG MODERN DIALOG SYSTEM
 * Replaces ugly browser alert() & confirm() with modern, accessible, beautiful popup modals.
 */
(function (window, document) {
    'use strict';

    let dialogContainer = null;
    let activeResolve = null;

    const ICONS = {
        danger: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
            <line x1="12" y1="9" x2="12" y2="13"/>
            <line x1="12" y1="17" x2="12.01" y2="17"/>
        </svg>`,
        success: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
            <polyline points="22 4 12 14.01 9 11.01"/>
        </svg>`,
        warning: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>`,
        info: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="16" x2="12" y2="12"/>
            <line x1="12" y1="8" x2="12.01" y2="8"/>
        </svg>`,
        lock: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>`
    };

    function ensureContainer() {
        if (dialogContainer && document.body.contains(dialogContainer)) return dialogContainer;

        dialogContainer = document.createElement('div');
        dialogContainer.id = 'tandangGlobalDialogBackdrop';
        dialogContainer.className = 'tandang-dialog-backdrop';
        dialogContainer.innerHTML = `
            <div class="tandang-dialog-box" role="dialog" aria-modal="true">
                <div class="tandang-dialog-icon danger" id="tandangDialogIcon"></div>
                <h3 class="tandang-dialog-title" id="tandangDialogTitle">Konfirmasi</h3>
                <div class="tandang-dialog-message" id="tandangDialogMessage"></div>
                <div class="tandang-dialog-notice" id="tandangDialogNotice" style="display: none;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <span id="tandangDialogNoticeText"></span>
                </div>
                <div class="tandang-dialog-actions" id="tandangDialogActions"></div>
            </div>
        `;

        document.body.appendChild(dialogContainer);

        // Click on backdrop to cancel
        dialogContainer.addEventListener('click', function (e) {
            if (e.target === dialogContainer) {
                closeDialog(false);
            }
        });

        // ESC key to cancel
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && dialogContainer.classList.contains('show')) {
                closeDialog(false);
            }
        });

        return dialogContainer;
    }

    function closeDialog(result) {
        if (!dialogContainer) return;
        dialogContainer.classList.remove('show');
        if (activeResolve) {
            const resolve = activeResolve;
            activeResolve = null;
            resolve(result);
        }
    }

    /**
     * Show Modern Confirm Dialog
     * @param {Object} options
     * @returns {Promise<boolean>}
     */
    function tandangConfirm(options = {}) {
        return new Promise((resolve) => {
            ensureContainer();
            activeResolve = resolve;

            const type = options.type || (options.confirmType === 'success' ? 'success' : 'danger');
            const title = options.title || 'Konfirmasi Tindakan';
            const message = options.message || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
            const notice = options.notice || '';
            const confirmText = options.confirmText || (type === 'danger' ? 'Ya, Lanjutkan' : 'Ya, Setuju');
            const cancelText = options.cancelText || 'Batal';

            const iconEl = document.getElementById('tandangDialogIcon');
            const titleEl = document.getElementById('tandangDialogTitle');
            const msgEl = document.getElementById('tandangDialogMessage');
            const noticeEl = document.getElementById('tandangDialogNotice');
            const noticeTextEl = document.getElementById('tandangDialogNoticeText');
            const actionsEl = document.getElementById('tandangDialogActions');

            iconEl.className = `tandang-dialog-icon ${type}`;
            iconEl.innerHTML = ICONS[options.icon] || ICONS[type] || ICONS.danger;

            titleEl.innerText = title;
            msgEl.innerHTML = message;

            if (notice) {
                noticeTextEl.innerText = notice;
                noticeEl.style.display = 'flex';
            } else {
                noticeEl.style.display = 'none';
            }

            const btnClass = type === 'success' ? 'tandang-dialog-btn-success' : 'tandang-dialog-btn-danger';

            actionsEl.innerHTML = `
                <button type="button" class="tandang-dialog-btn tandang-dialog-btn-secondary" id="tandangDialogCancelBtn">${cancelText}</button>
                <button type="button" class="tandang-dialog-btn ${btnClass}" id="tandangDialogConfirmBtn">${confirmText}</button>
            `;

            document.getElementById('tandangDialogCancelBtn').onclick = () => closeDialog(false);
            document.getElementById('tandangDialogConfirmBtn').onclick = () => {
                closeDialog(true);
                if (typeof options.onConfirm === 'function') {
                    options.onConfirm();
                }
            };

            dialogContainer.classList.add('show');
            document.getElementById('tandangDialogConfirmBtn').focus();
        });
    }

    /**
     * Show Modern Alert Dialog
     * @param {Object|string} options
     * @returns {Promise<void>}
     */
    function tandangAlert(options = {}) {
        return new Promise((resolve) => {
            ensureContainer();
            activeResolve = resolve;

            if (typeof options === 'string') {
                options = { message: options };
            }

            const type = options.type || 'info';
            const title = options.title || 'Informasi';
            const message = options.message || '';
            const buttonText = options.buttonText || 'Mengerti';

            const iconEl = document.getElementById('tandangDialogIcon');
            const titleEl = document.getElementById('tandangDialogTitle');
            const msgEl = document.getElementById('tandangDialogMessage');
            const noticeEl = document.getElementById('tandangDialogNotice');
            const actionsEl = document.getElementById('tandangDialogActions');

            iconEl.className = `tandang-dialog-icon ${type}`;
            iconEl.innerHTML = ICONS[type] || ICONS.info;

            titleEl.innerText = title;
            msgEl.innerHTML = message;
            noticeEl.style.display = 'none';

            actionsEl.innerHTML = `
                <button type="button" class="tandang-dialog-btn tandang-dialog-btn-primary" id="tandangDialogOkBtn">${buttonText}</button>
            `;

            document.getElementById('tandangDialogOkBtn').onclick = () => {
                closeDialog(true);
                if (typeof options.onOk === 'function') {
                    options.onOk();
                }
            };

            dialogContainer.classList.add('show');
            document.getElementById('tandangDialogOkBtn').focus();
        });
    }

    // Expose to window
    window.tandangConfirm = tandangConfirm;
    window.tandangAlert = tandangAlert;

    // Automatic Form & Button Interceptor for data-confirm
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!form || !form.hasAttribute('data-confirm')) return;
        if (form.dataset.confirmed === 'true') {
            form.dataset.confirmed = 'false';
            return; // Let normal submit proceed
        }

        e.preventDefault();
        tandangConfirm({
            title: form.getAttribute('data-confirm-title') || 'Konfirmasi Tindakan',
            message: form.getAttribute('data-confirm'),
            notice: form.getAttribute('data-confirm-notice') || '',
            type: form.getAttribute('data-confirm-type') || 'danger',
            icon: form.getAttribute('data-confirm-icon') || null,
            confirmText: form.getAttribute('data-confirm-btn') || 'Ya, Lanjutkan',
            cancelText: form.getAttribute('data-cancel-btn') || 'Batal',
            onConfirm: () => {
                form.dataset.confirmed = 'true';
                form.submit();
            }
        });
    }, true);

})(window, document);

