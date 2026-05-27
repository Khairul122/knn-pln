<?php
/**
 * Reusable toast notification partial.
 * Expects from parent scope:
 *   $flash (array|null) — from Flash::get(), shape: ['type' => 'success'|'error', 'message' => '...']
 *
 * Outputs: toast container div + all JS for showToast / dismissToast.
 * Call showToast(type, message) anywhere after including this file.
 */
?>
<div id="toast-container"
     class="fixed top-20 right-4 sm:right-5 z-50 flex flex-col gap-2.5 pointer-events-none w-[calc(100vw-2rem)] sm:w-auto sm:max-w-sm"></div>

<?php if (!empty($flash)): ?>
<script>window.__FLASH__ = <?= json_encode($flash) ?>;</script>
<?php endif; ?>

<script>
(function () {
    function showToast(type, message) {
        const container = document.getElementById('toast-container');
        const isSuccess = type === 'success';
        const toast = document.createElement('div');
        toast.dataset.toast = '1';
        toast.className = [
            'pointer-events-auto w-full flex flex-col gap-0 rounded-xl shadow-xl border overflow-hidden',
            isSuccess ? 'bg-white border-green-200' : 'bg-white border-red-200'
        ].join(' ');
        toast.innerHTML = `
            <div class="flex items-start gap-3 px-4 py-3.5">
                <span class="material-symbols-outlined text-[20px] mt-0.5 flex-shrink-0 ${isSuccess ? 'text-green-500' : 'text-red-500'}"
                      style="font-variation-settings:'FILL' 1;">${isSuccess ? 'check_circle' : 'error'}</span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold ${isSuccess ? 'text-green-800' : 'text-red-800'}">${isSuccess ? 'Berhasil' : 'Gagal'}</p>
                    <p class="text-xs mt-0.5 ${isSuccess ? 'text-green-700' : 'text-red-700'} leading-snug">${message}</p>
                </div>
                <button data-close class="text-outline/60 hover:text-outline transition-colors flex-shrink-0 -mr-1 -mt-0.5 p-0.5 rounded">
                    <span class="material-symbols-outlined text-[16px]">close</span>
                </button>
            </div>
            <div class="h-1 ${isSuccess ? 'bg-green-100' : 'bg-red-100'}">
                <div class="h-full ${isSuccess ? 'bg-green-400' : 'bg-red-400'} progress-bar" style="animation-duration:4.5s"></div>
            </div>`;
        toast.classList.add('toast-enter');
        toast.querySelector('[data-close]').addEventListener('click', () => dismissToast(toast));
        container.appendChild(toast);
        setTimeout(() => dismissToast(toast), 4500);
    }

    function dismissToast(t) {
        if (!t.isConnected) return;
        t.classList.replace('toast-enter', 'toast-leave');
        setTimeout(() => t.remove(), 260);
    }

    window.showToast = showToast;

    if (window.__FLASH__) showToast(window.__FLASH__.type, window.__FLASH__.message);
}());
</script>
