<?php
/**
 * Reusable sidebar partial.
 * Expects from parent scope:
 *   $baseUrl    (string)
 *   $userName   (string, already htmlspecialchars'd)
 *   $userEmail  (string, already htmlspecialchars'd)
 *   $userRole   (string)
 *   $activeMenu (string) — 'dashboard' | 'pemeliharaan' | 'prediksi' | 'laporan'
 */
$activeMenu ??= '';

$navItems = [
    ['key' => 'dashboard',    'href' => $baseUrl . '/dashboard',    'icon' => 'dashboard',      'label' => 'Dashboard'],
    ['key' => 'pemeliharaan', 'href' => $baseUrl . '/pemeliharaan', 'icon' => 'engineering',    'label' => 'Data Pemeliharaan'],
    ['key' => 'labeling',     'href' => $baseUrl . '/labeling',     'icon' => 'label',          'label' => 'Labeling FMEA'],
    ['key' => 'knn',          'href' => $baseUrl . '/knn/train',   'icon' => 'model_training', 'label' => 'Prediksi KNN'],
    ['key' => 'laporan',      'href' => $baseUrl . '/laporan',     'icon' => 'report_problem', 'label' => 'Laporan Risiko'],
];
if (($userRole ?? '') === 'admin') {
    $navItems[] = ['key' => 'pengaturan', 'href' => '#', 'icon' => 'settings', 'label' => 'Pengaturan'];
}
?>
<!-- Sidebar overlay (mobile) -->
<div id="sidebar-overlay"
     class="fixed inset-0 bg-black/40 z-40 hidden md:hidden"
     onclick="closeSidebar()"></div>

<!-- Sidebar -->
<aside id="sidebar"
       class="h-screen w-64 fixed left-0 top-0 bg-surface-container-low flex flex-col p-4 gap-2 shadow-sm z-50 -translate-x-full md:translate-x-0"
       style="transition: transform .3s cubic-bezier(.4,0,.2,1);">

    <button onclick="closeSidebar()"
            class="md:hidden absolute top-4 right-4 p-1.5 rounded-lg text-outline hover:bg-surface-container-high transition-colors">
        <span class="material-symbols-outlined text-[20px]">close</span>
    </button>

    <!-- Brand -->
    <div class="flex items-center gap-3 px-2 py-5">
        <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center text-white shadow flex-shrink-0">
            <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;">bolt</span>
        </div>
        <div>
            <h1 class="text-[16px] font-bold text-primary leading-tight">PLN GridRisk</h1>
            <p class="text-[10px] text-outline font-medium tracking-wider uppercase">Predictive Analytics</p>
        </div>
    </div>

    <!-- Nav -->
    <nav class="flex-1 flex flex-col gap-1 mt-2">
        <?php foreach ($navItems as $item):
            $isActive = $item['key'] === $activeMenu;
            $cls = $isActive
                ? 'bg-secondary-container text-on-secondary-container font-semibold'
                : 'text-on-surface-variant hover:bg-surface-container-high';
        ?>
        <a href="<?= $item['href'] ?>"
           class="<?= $cls ?> rounded-lg flex items-center gap-3 px-4 py-3 transition-colors">
            <span class="material-symbols-outlined"
                  <?= $isActive ? "style=\"font-variation-settings:'FILL' 1;\"" : '' ?>>
                <?= $item['icon'] ?>
            </span>
            <span class="text-sm"><?= $item['label'] ?></span>
        </a>
        <?php endforeach; ?>
    </nav>

    <!-- User info -->
    <div class="border-t border-outline-variant pt-4 px-2">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-primary flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                <?= strtoupper(substr($userName, 0, 2)) ?>
            </div>
            <div class="overflow-hidden flex-1">
                <p class="text-sm font-semibold truncate text-on-surface"><?= $userName ?></p>
                <p class="text-[11px] text-outline truncate"><?= $userEmail ?></p>
            </div>
            <a href="<?= $baseUrl ?>/logout" title="Logout"
               class="p-1.5 rounded-lg text-outline hover:text-error hover:bg-error-container transition-colors flex-shrink-0">
                <span class="material-symbols-outlined text-[20px]">logout</span>
            </a>
        </div>
    </div>
</aside>
