<?php
$flash      = Flash::get();
$baseUrl    = BASE_URL;
$userName   = htmlspecialchars($userName  ?? 'User');
$userEmail  = htmlspecialchars($userEmail ?? '');
$userRole   = $userRole ?? 'viewer';

// Partials config
$pageHeadTitle = 'Dashboard';
$activeMenu    = 'dashboard';
$pageTitle     = 'Monitoring Risiko Jaringan Listrik';
$pageIcon      = 'dashboard';
$headerActions = '
    <div class="hidden sm:flex items-center gap-2">
        <div class="h-8 w-px bg-outline-variant"></div>
        <span class="text-body-sm font-body-sm text-on-surface-variant whitespace-nowrap">Last update: 5 mins ago</span>
        <span class="material-symbols-outlined text-primary text-[18px]">sync</span>
    </div>
    <button class="relative p-2 hover:bg-surface-container rounded-full transition-colors">
        <span class="material-symbols-outlined text-on-surface-variant">notifications</span>
        <span class="absolute top-2 right-2 w-2 h-2 bg-error rounded-full"></span>
    </button>';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php require APP_PATH . '/Views/partials/head.php'; ?>
</head>
<body class="bg-background text-on-background">

<?php require APP_PATH . '/Views/partials/sidebar.php'; ?>
<?php require APP_PATH . '/Views/partials/header.php'; ?>

<!-- ── Main Content ──────────────────────────────────── -->
<main class="ml-0 md:ml-64 mt-16 p-4 sm:p-6 md:p-8 min-h-screen">

    <!-- Title -->
    <div class="mb-6 md:mb-8">
        <h2 class="text-[22px] sm:font-display-lg sm:text-display-lg text-primary font-bold leading-tight">Monitoring Risiko Jaringan Listrik</h2>
        <p class="font-body-sm text-body-sm sm:font-body-lg sm:text-body-lg text-on-surface-variant mt-1">Sistem Prediksi Berbasis Algoritma K-Nearest Neighbor (KNN)</p>
    </div>

    <!-- Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-6 md:mb-8">
        <div class="bg-surface-container-lowest p-5 sm:p-6 rounded-xl custom-shadow hover-card border-l-4 border-primary">
            <div class="flex justify-between items-start mb-4">
                <span class="material-symbols-outlined text-primary p-2 bg-surface-container rounded-lg">database</span>
                <span class="text-label-caps font-label-caps text-outline">+4.2%</span>
            </div>
            <p class="text-outline text-body-sm font-body-sm">Total Data Pemeliharaan</p>
            <h3 class="font-numeric-data text-numeric-data text-on-surface mt-1">1,240</h3>
        </div>
        <div class="bg-surface-container-lowest p-5 sm:p-6 rounded-xl custom-shadow hover-card border-l-4 border-error">
            <div class="flex justify-between items-start mb-4">
                <span class="material-symbols-outlined text-error p-2 bg-error-container rounded-lg">warning</span>
                <span class="text-label-caps font-label-caps text-error">Critical</span>
            </div>
            <p class="text-outline text-body-sm font-body-sm">Area Risiko Tinggi</p>
            <h3 class="font-numeric-data text-numeric-data text-on-surface mt-1">5 <span class="text-body-sm font-normal text-outline">Lokasi</span></h3>
        </div>
        <div class="bg-surface-container-lowest p-5 sm:p-6 rounded-xl custom-shadow hover-card border-l-4 border-secondary">
            <div class="flex justify-between items-start mb-4">
                <span class="material-symbols-outlined text-secondary p-2 bg-secondary-container/20 rounded-lg">verified</span>
                <span class="text-label-caps font-label-caps text-secondary">Optimized</span>
            </div>
            <p class="text-outline text-body-sm font-body-sm">Akurasi Prediksi KNN</p>
            <h3 class="font-numeric-data text-numeric-data text-on-surface mt-1">94.2%</h3>
        </div>
        <div class="bg-surface-container-lowest p-5 sm:p-6 rounded-xl custom-shadow hover-card border-l-4 border-tertiary">
            <div class="flex justify-between items-start mb-4">
                <span class="material-symbols-outlined text-tertiary p-2 bg-surface-container rounded-lg">task_alt</span>
                <span class="text-label-caps font-label-caps text-outline">Resolved</span>
            </div>
            <p class="text-outline text-body-sm font-body-sm">Gangguan Teratasi</p>
            <h3 class="font-numeric-data text-numeric-data text-on-surface mt-1">12 <span class="text-body-sm font-normal text-outline">Bulan ini</span></h3>
        </div>
    </div>

    <!-- Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 md:gap-8">

        <!-- Kiri: Peta + Tabel -->
        <div class="lg:col-span-2 space-y-6 md:space-y-8">

            <!-- Peta Grid -->
            <div class="bg-surface-container-lowest p-5 sm:p-6 rounded-xl custom-shadow">
                <div class="flex flex-wrap justify-between items-center gap-3 mb-5 sm:mb-6">
                    <h4 class="font-headline-md text-headline-md text-on-surface">Peta Status Grid Jaringan</h4>
                    <div class="flex gap-2">
                        <span class="inline-flex items-center gap-1 text-label-caps font-label-caps px-2 py-1 bg-surface-container-low text-on-surface-variant rounded">
                            <span class="w-2 h-2 rounded-full bg-primary"></span> Stabil
                        </span>
                        <span class="inline-flex items-center gap-1 text-label-caps font-label-caps px-2 py-1 bg-surface-container-low text-on-surface-variant rounded">
                            <span class="w-2 h-2 rounded-full bg-error"></span> Kritis
                        </span>
                    </div>
                </div>
                <div class="relative w-full h-[240px] sm:h-[300px] md:h-[340px] bg-surface rounded-lg overflow-hidden border border-outline-variant">
                    <img src="<?= $baseUrl ?>/assets/grid-map.jpg" alt="Peta Grid Jaringan"
                         class="w-full h-full object-cover opacity-85">
                    <div class="absolute top-1/4 left-1/3 w-4 h-4 bg-error rounded-full animate-ping opacity-75"></div>
                    <div class="absolute top-1/4 left-1/3 w-3 h-3 bg-error rounded-full"></div>
                    <div class="absolute bottom-1/3 right-1/4 w-4 h-4 bg-error rounded-full animate-ping opacity-75"></div>
                    <div class="absolute bottom-1/3 right-1/4 w-3 h-3 bg-error rounded-full"></div>
                </div>
            </div>

            <!-- Tabel Prediksi -->
            <div class="bg-surface-container-lowest p-5 sm:p-6 rounded-xl custom-shadow">
                <div class="flex flex-wrap justify-between items-center gap-3 mb-5 sm:mb-6">
                    <h4 class="font-headline-md text-headline-md text-on-surface">Prediksi Risiko Terbaru</h4>
                    <a href="<?= $baseUrl ?>/pemeliharaan" class="text-primary font-body-sm text-body-sm font-semibold flex items-center gap-1 hover:underline">
                        Lihat Semua <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                    </a>
                </div>
                <div class="overflow-x-auto -mx-1">
                    <table class="w-full text-left min-w-[500px]">
                        <thead>
                            <tr class="border-b border-outline-variant">
                                <th class="py-3 font-label-caps text-label-caps text-outline uppercase">Area / Gardu</th>
                                <th class="py-3 font-label-caps text-label-caps text-outline uppercase text-center">Tegangan (kV)</th>
                                <th class="py-3 font-label-caps text-label-caps text-outline uppercase text-center">Beban (MW)</th>
                                <th class="py-3 font-label-caps text-label-caps text-outline uppercase text-center">Suhu (°C)</th>
                                <th class="py-3 font-label-caps text-label-caps text-outline uppercase">Prediksi</th>
                            </tr>
                        </thead>
                        <tbody class="font-body-sm text-body-sm">
                            <?php
                            $rows  = [
                                ['Gardu Induk Gambir 01',  '150.2', '45.8', '38.5', 'low'],
                                ['Gardu Menteng Sentral',  '148.5', '78.2', '54.2', 'high'],
                                ['Trafo Dist. Sudirman A', '20.4',  '12.5', '42.1', 'medium'],
                                ['Gardu Kebayoran Baru',   '151.1', '34.9', '32.8', 'low'],
                            ];
                            $badge = ['low'=>'bg-green-100 text-green-800','medium'=>'bg-amber-100 text-amber-800','high'=>'bg-red-100 text-red-800'];
                            $label = ['low'=>'Low Risk','medium'=>'Medium Risk','high'=>'High Risk'];
                            foreach ($rows as $r): ?>
                            <tr class="border-b border-surface-container-low hover:bg-surface-container-low/60 transition-colors">
                                <td class="py-3.5 font-semibold text-on-surface"><?= $r[0] ?></td>
                                <td class="py-3.5 text-center text-on-surface-variant"><?= $r[1] ?></td>
                                <td class="py-3.5 text-center text-on-surface-variant"><?= $r[2] ?></td>
                                <td class="py-3.5 text-center text-on-surface-variant"><?= $r[3] ?></td>
                                <td class="py-3.5"><span class="px-3 py-1 rounded-full text-[12px] font-semibold <?= $badge[$r[4]] ?>"><?= $label[$r[4]] ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Kanan: Chart + Log + Export -->
        <div class="space-y-6 md:space-y-8">

            <!-- Distribusi Risiko -->
            <div class="bg-surface-container-lowest p-5 sm:p-6 rounded-xl custom-shadow">
                <h4 class="font-headline-md text-headline-md text-on-surface mb-5 sm:mb-6">Distribusi Risiko</h4>
                <div class="flex flex-col items-center py-2">
                    <div class="relative w-40 h-40 sm:w-44 sm:h-44">
                        <svg viewBox="0 0 100 100" class="w-full h-full -rotate-90">
                            <circle cx="50" cy="50" r="40" fill="none" stroke="#e5eeff" stroke-width="14"/>
                            <circle cx="50" cy="50" r="40" fill="none" stroke="#006492" stroke-width="14" stroke-dasharray="206 251" stroke-linecap="round"/>
                            <circle cx="50" cy="50" r="40" fill="none" stroke="#ba1a1a" stroke-width="14" stroke-dasharray="45 251" stroke-dashoffset="-206" stroke-linecap="round" opacity=".7"/>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                            <span class="font-numeric-data text-numeric-data text-primary">82%</span>
                            <p class="text-[10px] text-outline font-label-caps uppercase mt-0.5">System Health</p>
                        </div>
                    </div>
                    <div class="w-full mt-6 space-y-4">
                        <?php foreach ([
                            ['Rendah (Low)',    '642 Unit', 'bg-green-500', '65%'],
                            ['Sedang (Medium)', '418 Unit', 'bg-amber-500', '25%'],
                            ['Tinggi (High)',   '180 Unit', 'bg-red-500',   '10%'],
                        ] as $d): ?>
                        <div>
                            <div class="flex justify-between items-center mb-1.5">
                                <div class="flex items-center gap-2">
                                    <div class="w-2.5 h-2.5 rounded-full <?= $d[2] ?>"></div>
                                    <span class="font-body-sm text-body-sm text-on-surface-variant"><?= $d[0] ?></span>
                                </div>
                                <span class="font-semibold text-body-sm text-on-surface"><?= $d[1] ?></span>
                            </div>
                            <div class="w-full h-1.5 bg-surface-container rounded-full overflow-hidden">
                                <div class="<?= $d[2] ?> h-full rounded-full" style="width:<?= $d[3] ?>"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Log Aktivitas KNN -->
            <div class="bg-surface-container-lowest p-5 sm:p-6 rounded-xl custom-shadow">
                <h4 class="font-headline-md text-headline-md text-on-surface mb-5 sm:mb-6">Log Aktivitas KNN</h4>
                <div class="space-y-5">
                    <?php
                    $logs = [
                        ['primary',   'check_circle', 'Prediksi Selesai',    'Model KNN melakukan kalkulasi pada 1,240 titik data pemeliharaan terbaru.', '10:45 AM'],
                        ['error',     'warning',      'Alert: Gardu Menteng', 'Risiko Tinggi terdeteksi pada area Menteng Sentral akibat beban berlebih.', '09:12 AM'],
                        ['secondary', 'upload_file',  'Input Data Baru',      'Data pemeliharaan bulanan Regional Jakarta Selatan telah diimpor.',          'Kemarin'],
                    ];
                    $logColor = ['primary'=>'text-primary bg-primary-fixed/40','error'=>'text-error bg-error-container','secondary'=>'text-secondary bg-secondary-container/30'];
                    foreach ($logs as $i => $log): ?>
                    <div class="flex gap-3">
                        <div class="flex flex-col items-center flex-shrink-0">
                            <span class="material-symbols-outlined text-[18px] p-1 rounded-full <?= $logColor[$log[0]] ?>"
                                  style="font-variation-settings:'FILL' 1;"><?= $log[1] ?></span>
                            <?php if ($i < count($logs) - 1): ?>
                            <div class="w-px flex-1 bg-outline-variant/50 mt-1 min-h-[20px]"></div>
                            <?php endif; ?>
                        </div>
                        <div class="pb-4">
                            <p class="font-body-sm text-body-sm font-semibold text-on-surface"><?= $log[2] ?></p>
                            <p class="text-[12px] text-outline mt-0.5 leading-relaxed"><?= $log[3] ?></p>
                            <span class="text-[10px] text-outline/70 mt-1.5 block"><?= $log[4] ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Generate Report -->
            <div class="bg-primary p-5 sm:p-6 rounded-xl custom-shadow text-white relative overflow-hidden group">
                <div class="absolute -right-8 -bottom-8 w-32 h-32 bg-white/10 rounded-full group-hover:scale-125 transition-transform duration-500"></div>
                <div class="absolute -right-2 -top-4 w-20 h-20 bg-white/5 rounded-full"></div>
                <h4 class="font-headline-md text-headline-md font-bold mb-2 relative z-10">Unduh Laporan</h4>
                <p class="text-body-sm font-body-sm opacity-80 mb-6 text-white/90 relative z-10">
                    Dapatkan laporan analitik risiko komprehensif dalam format PDF atau Excel.
                </p>
                <button class="w-full py-3 bg-white text-primary rounded-lg font-semibold flex items-center justify-center gap-2 hover:bg-surface-container transition-colors relative z-10">
                    <span class="material-symbols-outlined text-[20px]">download</span>
                    Generate Report
                </button>
            </div>
        </div>
    </div>
</main>

<?php require APP_PATH . '/Views/partials/toast.php'; ?>
<?php require APP_PATH . '/Views/partials/sidebar_script.php'; ?>
</body>
</html>
