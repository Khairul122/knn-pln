<?php
$flash   = Flash::get();
$baseUrl = BASE_URL;
?>
<!DOCTYPE html>
<html lang="id" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — PLN GridRisk</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary-fixed": "#cae6ff", "secondary-fixed": "#d3e4ff",
                        "surface-container-low": "#eff4ff", "background": "#f8f9ff",
                        "on-primary-fixed-variant": "#004b6f", "on-primary": "#ffffff",
                        "secondary-container": "#7eb6fe", "on-surface": "#0b1c30",
                        "surface-container-high": "#dce9ff", "secondary": "#1b60a2",
                        "on-error-container": "#93000a", "error-container": "#ffdad6",
                        "on-error": "#ffffff", "surface": "#f8f9ff",
                        "outline-variant": "#bec8d2", "on-secondary-container": "#00477e",
                        "error": "#ba1a1a", "surface-container": "#e5eeff",
                        "on-background": "#0b1c30", "primary": "#006492", "outline": "#6f7f8c",
                    },
                    borderRadius: { "DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px" },
                    fontFamily: { "body-lg": ["Inter"], "display-lg": ["Inter"], "headline-md": ["Inter"], "label-caps": ["Inter"] },
                    fontSize: {
                        "body-sm":    ["14px", { lineHeight: "20px", fontWeight: "400" }],
                        "body-lg":    ["16px", { lineHeight: "24px", fontWeight: "400" }],
                        "display-lg": ["32px", { lineHeight: "40px", letterSpacing: "-0.02em", fontWeight: "700" }],
                        "headline-md":["20px", { lineHeight: "28px", letterSpacing: "-0.01em", fontWeight: "600" }],
                        "label-caps": ["12px", { lineHeight: "16px", letterSpacing: "0.05em", fontWeight: "600" }],
                    }
                }
            }
        }
    </script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .bg-grid-pattern { background-image: radial-gradient(#006492 0.5px, transparent 0.5px); background-size: 24px 24px; }
        .gradient-mesh {
            background-color: #f8f9ff;
            background-image: radial-gradient(at 0% 0%, hsla(202,100%,90%,1) 0, transparent 50%),
                              radial-gradient(at 100% 100%, hsla(210,100%,95%,1) 0, transparent 50%);
        }
        @keyframes toast-in  { from { opacity:0; transform:translateX(16px); } to { opacity:1; transform:translateX(0); } }
        @keyframes toast-out { from { opacity:1; transform:translateX(0); } to { opacity:0; transform:translateX(16px); } }
        .toast-enter { animation: toast-in  .3s cubic-bezier(.22,1,.36,1) forwards; }
        .toast-leave { animation: toast-out .25s ease forwards; }
        .progress-bar { animation: progress-shrink linear forwards; }
        @keyframes progress-shrink { from { width: 100%; } to { width: 0%; } }
    </style>
</head>
<body class="bg-background font-body-lg text-on-background min-h-screen flex items-center justify-center p-4 sm:p-6 gradient-mesh overflow-hidden relative">

<!-- Decorative bg -->
<div class="absolute inset-0 bg-grid-pattern opacity-10 pointer-events-none" id="bg-grid"></div>
<div class="absolute top-[-10%] right-[-10%] w-[50%] h-[50%] bg-primary-fixed/20 rounded-full blur-[120px] pointer-events-none"></div>
<div class="absolute bottom-[-10%] left-[-10%] w-[40%] h-[40%] bg-secondary-fixed/20 rounded-full blur-[100px] pointer-events-none"></div>

<main class="w-full max-w-5xl grid grid-cols-1 md:grid-cols-2 rounded-2xl overflow-hidden shadow-2xl relative z-10 bg-white">

    <!-- Kiri: Visual (tersembunyi di mobile) -->
    <div class="hidden md:flex flex-col justify-between p-10 lg:p-12 bg-primary relative overflow-hidden min-h-[560px]">
        <img class="absolute inset-0 w-full h-full object-cover opacity-30 mix-blend-overlay"
             src="<?= $baseUrl ?>/assets/grid-bg.jpg" alt="Power grid">
        <div class="relative z-20 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center shadow-lg flex-shrink-0">
                    <span class="material-symbols-outlined text-primary text-[28px]" style="font-variation-settings:'FILL' 1;">bolt</span>
                </div>
                <span class="font-headline-md text-white tracking-tight">PLN GridRisk</span>
            </div>
            <a href="<?= $baseUrl ?>/"
               class="flex items-center gap-1 text-xs text-white/70 hover:text-white transition-colors">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span> Beranda
            </a>
        </div>
        <div class="relative z-20 mt-auto">
            <h1 class="font-display-lg text-white mb-4 text-[26px] lg:text-[32px]">Protecting the Grid<br>Through Intelligence.</h1>
            <p class="text-sm lg:text-base text-primary-fixed opacity-90 max-w-sm leading-relaxed">
                Leveraging advanced KNN predictive analytics to maintain national power stability and minimize infrastructure risks.
            </p>
        </div>
    </div>

    <!-- Kanan: Form -->
    <div class="p-6 sm:p-8 md:p-10 lg:p-12 flex flex-col justify-between bg-white min-h-[560px]">

        <!-- Logo mobile / Spacer -->
        <div class="md:hidden text-center">
            <a href="<?= $baseUrl ?>/" class="inline-flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-[32px]" style="font-variation-settings:'FILL' 1;">bolt</span>
                <span class="font-headline-md text-primary font-bold">PLN GridRisk</span>
            </a>
            <p class="text-xs text-outline mt-1 tracking-wider uppercase">Predictive Analytics</p>
        </div>
        <div class="hidden md:block"></div>

        <!-- Main Form Wrapper -->
        <div class="my-auto py-6">
            <!-- Heading -->
            <div class="mb-7">
                <h2 class="text-[26px] sm:font-display-lg sm:text-display-lg text-on-surface font-bold mb-1">Selamat Datang</h2>
                <p class="text-sm text-outline">Silakan masuk untuk mengakses dasbor analitik Anda.</p>
            </div>

            <!-- Form -->
            <form class="space-y-5" id="loginForm" method="POST" action="<?= BASE_URL ?>/login">

                <!-- Email -->
                <div class="space-y-1.5">
                    <label class="font-label-caps text-on-surface-variant text-[11px] uppercase tracking-widest font-semibold" for="email">Email atau Username</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-outline group-focus-within:text-primary transition-colors">
                            <span class="material-symbols-outlined text-[20px]">person</span>
                        </div>
                        <input
                            class="block w-full pl-11 pr-4 py-3 sm:py-3.5 bg-surface-container-low border-0 rounded-xl text-sm text-on-surface ring-1 ring-inset ring-outline-variant focus:ring-2 focus:ring-primary focus:bg-white transition-all outline-none"
                            id="email" name="email" type="text"
                            placeholder="john.doe@pln.co.id"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            required autocomplete="email">
                    </div>
                </div>

                <!-- Password -->
                <div class="space-y-1.5">
                    <div class="flex justify-between items-center">
                        <label class="font-label-caps text-on-surface-variant text-[11px] uppercase tracking-widest font-semibold" for="password">Kata Sandi</label>
                        <a class="text-xs font-semibold text-primary hover:text-on-secondary-container transition-colors" href="#">Lupa Kata Sandi?</a>
                    </div>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-outline group-focus-within:text-primary transition-colors">
                            <span class="material-symbols-outlined text-[20px]">lock</span>
                        </div>
                        <input
                            class="block w-full pl-11 pr-11 py-3 sm:py-3.5 bg-surface-container-low border-0 rounded-xl text-sm text-on-surface ring-1 ring-inset ring-outline-variant focus:ring-2 focus:ring-primary focus:bg-white transition-all outline-none"
                            id="password" name="password" type="password"
                            placeholder="••••••••••••"
                            required autocomplete="current-password">
                        <button class="absolute inset-y-0 right-0 pr-4 flex items-center text-outline hover:text-on-surface transition-colors"
                                onclick="togglePassword()" type="button" tabindex="-1">
                            <span class="material-symbols-outlined text-[20px]" id="passwordIcon">visibility</span>
                        </button>
                    </div>
                </div>

                <!-- Ingat Saya -->
                <div class="flex items-center">
                    <input class="h-4 w-4 rounded border-outline-variant text-primary focus:ring-primary cursor-pointer"
                           id="remember-me" name="remember_me" type="checkbox">
                    <label class="ml-2.5 block text-sm text-on-surface-variant cursor-pointer select-none" for="remember-me">
                        Ingat Saya
                    </label>
                </div>

                <!-- Tombol -->
                <button id="submitBtn"
                    class="w-full flex justify-center items-center gap-2 py-3.5 px-6 rounded-xl bg-primary text-white text-sm font-semibold shadow-lg shadow-primary/20 hover:bg-on-primary-fixed-variant hover:-translate-y-0.5 transition-all active:translate-y-0 mt-2"
                    type="submit">
                    Masuk
                    <span class="material-symbols-outlined text-[18px]">login</span>
                </button>
            </form>
        </div>

        <!-- Footer -->
        <footer class="text-center">
            <p class="text-xs text-outline">&copy; <?= date('Y') ?> PT PLN (Persero).</p>
        </footer>
    </div>
</main>

<!-- Toast container: pojok kanan atas -->
<div id="toast-container" class="fixed top-5 right-5 z-50 flex flex-col gap-2.5 pointer-events-none w-[calc(100vw-2.5rem)] sm:w-auto sm:max-w-sm"></div>

<?php if ($flash): ?>
<script>window.__FLASH__ = <?= json_encode($flash) ?>;</script>
<?php endif; ?>

<script>
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

    function dismissToast(toast) {
        if (!toast.isConnected) return;
        toast.classList.replace('toast-enter', 'toast-leave');
        setTimeout(() => toast.remove(), 260);
    }

    if (window.__FLASH__) showToast(window.__FLASH__.type, window.__FLASH__.message);

    function togglePassword() {
        const inp  = document.getElementById('password');
        const icon = document.getElementById('passwordIcon');
        inp.type = inp.type === 'password' ? 'text' : 'password';
        icon.innerText = inp.type === 'password' ? 'visibility' : 'visibility_off';
    }

    document.getElementById('loginForm').addEventListener('submit', function () {
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span><span>Memproses...</span>';
    });

    document.addEventListener('mousemove', function (e) {
        const mx = (e.clientX - window.innerWidth  / 2) * 0.012;
        const my = (e.clientY - window.innerHeight / 2) * 0.012;
        const g = document.getElementById('bg-grid');
        if (g) g.style.transform = 'translate(' + mx + 'px,' + my + 'px)';
    });
</script>
</body>
</html>
