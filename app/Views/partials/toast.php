<?php
/**
 * Shared UI utilities partial — toast notifications + confirm/alert dialog.
 * Include once per page, just before </body>.
 *
 * JS globals exposed:
 *   showToast(type, message)              — 'success' | 'error'
 *   showDialog({ title, message, confirmText, cancelText, type, onConfirm })
 *   showAlert(message, title?)            — info-only, single OK button
 *
 * Forms with data-confirm="…" are intercepted automatically.
 * Optional: data-confirm-title, data-confirm-type (danger|warning|info), data-confirm-ok
 */
?>

<!-- Toast container -->
<div id="toast-container"
     class="fixed top-4 right-4 sm:right-5 z-[9998] flex flex-col gap-2.5 pointer-events-none w-[calc(100vw-2rem)] sm:w-auto sm:max-w-sm"></div>

<!-- Dialog overlay -->
<div id="dlg-overlay"
     class="fixed inset-0 z-[9999] bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 hidden">
    <div id="dlg-box"
         class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
        <div class="p-6 pb-4">
            <div class="flex items-start gap-4">
                <span id="dlg-icon"
                      class="material-symbols-outlined text-[30px] flex-shrink-0 mt-0.5"
                      style="font-variation-settings:'FILL' 1;"></span>
                <div class="flex-1 min-w-0">
                    <h3 id="dlg-title" class="text-base font-semibold text-on-surface"></h3>
                    <p id="dlg-message"
                       class="text-sm text-on-surface-variant mt-1.5 leading-relaxed"
                       style="white-space:pre-line;"></p>
                </div>
            </div>
        </div>
        <div id="dlg-actions" class="flex justify-end gap-2 px-4 pb-4"></div>
    </div>
</div>

<?php if (!empty($flash)): ?>
<script>window.__FLASH__ = <?= json_encode($flash) ?>;</script>
<?php endif; ?>

<script>
(function () {
    /* ── Toast ─────────────────────────────────────────────────────────── */
    function showToast(type, message) {
        const container = document.getElementById('toast-container');
        const ok  = type === 'success';
        const t   = document.createElement('div');
        t.dataset.toast = '1';
        t.className = [
            'pointer-events-auto w-full flex flex-col gap-0 rounded-xl shadow-xl border overflow-hidden',
            ok ? 'bg-white border-green-200' : 'bg-white border-red-200'
        ].join(' ');
        t.innerHTML = `
            <div class="flex items-start gap-3 px-4 py-3.5">
                <span class="material-symbols-outlined text-[20px] mt-0.5 flex-shrink-0 ${ok ? 'text-green-500' : 'text-red-500'}"
                      style="font-variation-settings:'FILL' 1;">${ok ? 'check_circle' : 'error'}</span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold ${ok ? 'text-green-800' : 'text-red-800'}">${ok ? 'Berhasil' : 'Gagal'}</p>
                    <p class="text-xs mt-0.5 ${ok ? 'text-green-700' : 'text-red-700'} leading-snug">${message}</p>
                </div>
                <button data-close class="text-outline/60 hover:text-outline transition-colors flex-shrink-0 -mr-1 -mt-0.5 p-0.5 rounded">
                    <span class="material-symbols-outlined text-[16px]">close</span>
                </button>
            </div>
            <div class="h-1 ${ok ? 'bg-green-100' : 'bg-red-100'}">
                <div class="h-full ${ok ? 'bg-green-400' : 'bg-red-400'} progress-bar" style="animation-duration:4.5s"></div>
            </div>`;
        t.classList.add('toast-enter');
        t.querySelector('[data-close]').addEventListener('click', () => dismissToast(t));
        container.appendChild(t);
        setTimeout(() => dismissToast(t), 4500);
    }

    function dismissToast(t) {
        if (!t.isConnected) return;
        t.classList.replace('toast-enter', 'toast-leave');
        setTimeout(() => t.remove(), 260);
    }

    window.showToast = showToast;
    if (window.__FLASH__) showToast(window.__FLASH__.type, window.__FLASH__.message);

    /* ── Dialog ─────────────────────────────────────────────────────────── */
    const overlay = document.getElementById('dlg-overlay');
    const box     = document.getElementById('dlg-box');
    const iconEl  = document.getElementById('dlg-icon');
    const titleEl = document.getElementById('dlg-title');
    const msgEl   = document.getElementById('dlg-message');
    const actions = document.getElementById('dlg-actions');

    const DLG_CFG = {
        danger:  { icon: 'delete_forever', iconCls: 'text-error',    btnCls: 'bg-error text-white hover:opacity-90' },
        warning: { icon: 'warning',        iconCls: 'text-amber-500', btnCls: 'bg-primary text-white hover:bg-on-primary-fixed-variant' },
        info:    { icon: 'info',           iconCls: 'text-primary',   btnCls: 'bg-primary text-white hover:bg-on-primary-fixed-variant' },
        alert:   { icon: 'error_outline',  iconCls: 'text-amber-500', btnCls: 'bg-primary text-white hover:bg-on-primary-fixed-variant' },
    };

    function closeDialog() {
        box.classList.replace('dlg-enter', 'dlg-leave');
        setTimeout(() => {
            overlay.classList.add('hidden');
            box.classList.remove('dlg-leave');
        }, 150);
    }

    window.showDialog = function ({ title = 'Konfirmasi', message = '', confirmText = 'Ya, Lanjutkan',
                                    cancelText = 'Batal', type = 'warning', onConfirm } = {}) {
        const cfg = DLG_CFG[type] || DLG_CFG.warning;

        iconEl.className    = `material-symbols-outlined text-[30px] flex-shrink-0 mt-0.5 ${cfg.iconCls}`;
        iconEl.textContent  = cfg.icon;
        titleEl.textContent = title;
        msgEl.textContent   = message;

        actions.innerHTML = '';

        if (cancelText) {
            const btnCancel = document.createElement('button');
            btnCancel.type      = 'button';
            btnCancel.textContent = cancelText;
            btnCancel.className = 'px-4 py-2 text-sm font-semibold rounded-xl text-on-surface-variant hover:bg-surface-container transition-colors';
            btnCancel.onclick   = closeDialog;
            actions.appendChild(btnCancel);
        }

        const btnOk = document.createElement('button');
        btnOk.type      = 'button';
        btnOk.textContent = confirmText;
        btnOk.className = `px-4 py-2 text-sm font-semibold rounded-xl transition-colors ${cfg.btnCls}`;
        btnOk.onclick   = () => { closeDialog(); onConfirm && onConfirm(); };
        actions.appendChild(btnOk);

        overlay.classList.remove('hidden');
        requestAnimationFrame(() => {
            box.classList.remove('dlg-leave');
            box.classList.add('dlg-enter');
        });
    };

    window.showAlert = function (message, title = 'Perhatian') {
        showDialog({ title, message, confirmText: 'OK', cancelText: null, type: 'alert' });
    };

    /* Close on backdrop click */
    overlay.addEventListener('click', function (e) { if (e.target === overlay) closeDialog(); });

    /* Close on Escape */
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !overlay.classList.contains('hidden')) closeDialog();
    });

    /* Auto-intercept forms with data-confirm="…" */
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!form.dataset.confirm) return;
        if (form._dlgOk) { form._dlgOk = false; return; }
        e.preventDefault();
        showDialog({
            title:       form.dataset.confirmTitle || 'Konfirmasi',
            message:     form.dataset.confirm,
            type:        form.dataset.confirmType  || 'warning',
            confirmText: form.dataset.confirmOk    || 'Ya, Lanjutkan',
            onConfirm:   () => { form._dlgOk = true; form.requestSubmit(); },
        });
    });
}());
</script>
