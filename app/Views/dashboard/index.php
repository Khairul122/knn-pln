<?php
$flash   = Flash::get();
$baseUrl = BASE_URL;

$userName  = htmlspecialchars($userName  ?? 'User');
$userEmail = htmlspecialchars($userEmail ?? '');
$userRole  = $userRole ?? 'viewer';

$pageHeadTitle = 'Dashboard';
$activeMenu    = 'dashboard';
$pageTitle     = 'Dashboard';
$pageIcon      = 'dashboard';
$backUrl       = null;
$headerActions = null;

// ── Derived values ───────────────────────────────────────────────────────────
$total   = (int)($summary['total_pemeliharaan'] ?? 0);
$labeled = (int)($summary['total_labeled']      ?? 0);
$rendah  = (int)($summary['rendah']             ?? 0);
$sedang  = (int)($summary['sedang']             ?? 0);
$tinggi  = (int)($summary['tinggi']             ?? 0);
$avgRpn  = round((float)($summary['avg_rpn']    ?? 0), 1);
$maxRpn  = (int)($summary['max_rpn']            ?? 0);

$labeledPct = $total > 0 ? round($labeled / $total * 100) : 0;
$pct        = fn(int $n) => $labeled > 0 ? round($n / $labeled * 100, 1) : 0;

// Monthly chart
$namaBulan  = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
$monthlyMap = array_column($monthly ?? [], null, 'bulan');
$chartLabels = $chartR = $chartS = $chartT = [];
for ($b = 1; $b <= 12; $b++) {
    if (!isset($monthlyMap[$b])) continue;
    $chartLabels[] = $namaBulan[$b];
    $chartR[]      = (int)($monthlyMap[$b]['rendah'] ?? 0);
    $chartS[]      = (int)($monthlyMap[$b]['sedang'] ?? 0);
    $chartT[]      = (int)($monthlyMap[$b]['tinggi'] ?? 0);
}

// Workflow steps
$fmtDate = fn(?string $ts) => $ts ? date('d M Y H:i', strtotime($ts)) : null;
$workflow = [
    [
        'label' => 'Data Pemeliharaan',
        'icon'  => 'database',
        'done'  => $total > 0,
        'info'  => $total > 0 ? "{$total} record" : 'Belum ada data',
        'href'  => $baseUrl . '/pemeliharaan',
    ],
    [
        'label' => 'FMEA Labeling',
        'icon'  => 'label',
        'done'  => $labeled > 0,
        'info'  => $labeled > 0 ? "{$labeled} berlabel ({$labeledPct}%)" : 'Belum ada label',
        'href'  => $baseUrl . '/labeling',
    ],
    [
        'label' => 'Split Data',
        'icon'  => 'call_split',
        'done'  => $hasSplit,
        'info'  => $hasSplit ? 'Train / Test tersedia' : 'Belum di-split',
        'href'  => $baseUrl . '/labeling/split',
    ],
    [
        'label' => 'Training KNN',
        'icon'  => 'model_training',
        'done'  => $latestModel !== null && $latestModel !== false,
        'info'  => $latestModel ? 'K=' . $latestModel['k_value'] . ' · ' . round($latestModel['accuracy'] * 100, 1) . '%' : 'Belum dilatih',
        'href'  => $baseUrl . '/knn/train',
    ],
    [
        'label' => 'Prediksi Batch',
        'icon'  => 'batch_prediction',
        'done'  => $hasPredictions,
        'info'  => $hasPredictions ? 'Prediksi tersedia' : 'Belum diprediksi',
        'href'  => $baseUrl . '/knn/predict',
    ],
];

$doneCount = count(array_filter($workflow, fn($s) => $s['done']));
$workflowPct = round($doneCount / count($workflow) * 100);

// Risk badge helper
$riskBadge = [
    'Rendah' => 'bg-green-100 text-green-800',
    'Sedang' => 'bg-amber-100 text-amber-800',
    'Tinggi' => 'bg-red-100 text-red-800',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php require APP_PATH . '/Views/partials/head.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="bg-background text-on-background">

<?php require APP_PATH . '/Views/partials/sidebar.php'; ?>
<?php require APP_PATH . '/Views/partials/header.php'; ?>

<main class="ml-0 md:ml-64 mt-16 p-4 sm:p-6 min-h-screen">

    <!-- ── Page header + year filter ── -->
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl font-bold text-on-surface">
                Selamat datang, <?= explode(' ', $userName)[0] ?>
            </h2>
            <p class="text-sm text-outline mt-0.5">Monitoring Risiko Jaringan · Tahun <?= $tahun ?></p>
        </div>
        <form method="GET" action="<?= $baseUrl ?>/dashboard" class="flex items-center gap-3">
            <label class="text-xs text-outline font-semibold">Tahun:</label>
            <select name="tahun" onchange="this.form.submit()"
                    class="text-sm border border-outline-variant rounded-lg pl-3 pr-8 py-1.5 bg-white text-on-surface focus:ring-1 focus:ring-primary outline-none w-24">
                <?php foreach ($years as $y): ?>
                <option value="<?= $y ?>" <?= $y == $tahun ? 'selected' : '' ?>><?= $y ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <!-- ── Metric Cards ── -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

        <!-- Total Data -->
        <div class="bg-surface-container-lowest rounded-xl border-l-4 border-primary p-5 shadow-sm">
            <div class="flex justify-between items-start mb-3">
                <span class="material-symbols-outlined text-primary p-2 bg-primary/10 rounded-lg text-[20px]">database</span>
                <span class="text-[11px] font-semibold text-outline"><?= $penyulangCount ?> penyulang</span>
            </div>
            <p class="text-[11px] text-outline uppercase tracking-wider">Total Pemeliharaan</p>
            <p class="text-3xl font-bold text-on-surface mt-0.5"><?= number_format($total) ?></p>
            <?php if ($total === 0): ?>
            <a href="<?= $baseUrl ?>/pemeliharaan" class="text-[11px] text-primary hover:underline mt-1 block">Import data →</a>
            <?php endif; ?>
        </div>

        <!-- Berlabel -->
        <div class="bg-surface-container-lowest rounded-xl border-l-4 border-secondary p-5 shadow-sm">
            <div class="flex justify-between items-start mb-3">
                <span class="material-symbols-outlined text-secondary p-2 bg-secondary/10 rounded-lg text-[20px]">label</span>
                <span class="text-[11px] font-semibold <?= $labeledPct >= 80 ? 'text-green-600' : ($labeledPct >= 40 ? 'text-amber-600' : 'text-outline') ?>">
                    <?= $labeledPct ?>%
                </span>
            </div>
            <p class="text-[11px] text-outline uppercase tracking-wider">Data Berlabel</p>
            <p class="text-3xl font-bold text-on-surface mt-0.5"><?= number_format($labeled) ?></p>
            <div class="mt-2 w-full h-1 bg-surface-container-high rounded-full overflow-hidden">
                <div class="h-full bg-secondary rounded-full" style="width:<?= $labeledPct ?>%"></div>
            </div>
        </div>

        <!-- Akurasi KNN -->
        <div class="bg-surface-container-lowest rounded-xl border-l-4 <?= $latestModel ? 'border-teal-500' : 'border-outline-variant' ?> p-5 shadow-sm">
            <div class="flex justify-between items-start mb-3">
                <span class="material-symbols-outlined text-teal-600 p-2 bg-teal-50 rounded-lg text-[20px]">verified</span>
                <?php if ($latestModel): ?>
                <span class="text-[11px] font-semibold text-teal-600">K=<?= $latestModel['k_value'] ?></span>
                <?php else: ?>
                <span class="text-[11px] text-outline">—</span>
                <?php endif; ?>
            </div>
            <p class="text-[11px] text-outline uppercase tracking-wider">Akurasi KNN</p>
            <p class="text-3xl font-bold <?= $latestModel ? 'text-teal-700' : 'text-outline/40' ?> mt-0.5">
                <?= $latestModel ? round($latestModel['accuracy'] * 100, 1) . '%' : '—' ?>
            </p>
            <?php if (!$latestModel): ?>
            <a href="<?= $baseUrl ?>/knn/train" class="text-[11px] text-primary hover:underline mt-1 block">Latih model →</a>
            <?php else: ?>
            <p class="text-[11px] text-outline mt-1"><?= $totalModels ?> model tersimpan</p>
            <?php endif; ?>
        </div>

        <!-- Risiko Tinggi -->
        <div class="bg-surface-container-lowest rounded-xl border-l-4 <?= $tinggi > 0 ? 'border-red-500' : 'border-green-400' ?> p-5 shadow-sm">
            <div class="flex justify-between items-start mb-3">
                <span class="material-symbols-outlined <?= $tinggi > 0 ? 'text-red-600' : 'text-green-600' ?> p-2 <?= $tinggi > 0 ? 'bg-red-50' : 'bg-green-50' ?> rounded-lg text-[20px]">
                    <?= $tinggi > 0 ? 'warning' : 'check_circle' ?>
                </span>
                <?php if ($tinggi > 0): ?>
                <span class="text-[11px] font-semibold text-red-600"><?= $pct($tinggi) ?>% dari berlabel</span>
                <?php else: ?>
                <span class="text-[11px] font-semibold text-green-600">Aman</span>
                <?php endif; ?>
            </div>
            <p class="text-[11px] text-outline uppercase tracking-wider">Risiko Tinggi</p>
            <p class="text-3xl font-bold <?= $tinggi > 0 ? 'text-red-600' : 'text-green-600' ?> mt-0.5">
                <?= $tinggi ?>
            </p>
            <p class="text-[11px] text-outline mt-1">Max RPN: <?= $maxRpn ?></p>
        </div>
    </div>

    <!-- ── Main grid ── -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- ── Left (2/3) ── -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Monthly chart -->
            <div class="bg-surface-container-lowest rounded-xl shadow-sm p-5">
                <div class="flex flex-wrap justify-between items-center gap-3 mb-4">
                    <div>
                        <h4 class="text-sm font-bold text-on-surface">Distribusi Risiko per Bulan</h4>
                        <p class="text-[11px] text-outline mt-0.5">Jumlah kasus berlabel · Tahun <?= $tahun ?></p>
                    </div>
                    <a href="<?= $baseUrl ?>/laporan?tahun=<?= $tahun ?>"
                       class="text-xs font-semibold text-primary hover:underline flex items-center gap-1">
                        Laporan lengkap <span class="material-symbols-outlined text-[14px]">open_in_new</span>
                    </a>
                </div>

                <?php if (!empty($chartLabels)): ?>
                <div class="h-56">
                    <canvas id="monthlyChart"></canvas>
                </div>
                <div class="flex gap-4 mt-3 justify-center">
                    <?php foreach ([
                        ['bg-green-500','Rendah',$rendah],
                        ['bg-amber-500','Sedang',$sedang],
                        ['bg-red-500',  'Tinggi',$tinggi],
                    ] as [$dot,$lbl,$cnt]): ?>
                    <div class="flex items-center gap-1.5 text-[11px] text-on-surface-variant">
                        <span class="w-2.5 h-2.5 rounded-full <?= $dot ?> flex-shrink-0"></span>
                        <?= $lbl ?> (<?= $cnt ?>)
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="h-56 flex flex-col items-center justify-center text-center">
                    <span class="material-symbols-outlined text-[48px] text-outline/30 mb-2">insert_chart</span>
                    <p class="text-sm text-outline">Belum ada data berlabel untuk tahun <?= $tahun ?></p>
                    <a href="<?= $baseUrl ?>/labeling" class="mt-2 text-xs font-semibold text-primary hover:underline">Mulai labeling →</a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Top high-risk table -->
            <div class="bg-surface-container-lowest rounded-xl shadow-sm p-5">
                <div class="flex flex-wrap justify-between items-center gap-3 mb-4">
                    <div>
                        <h4 class="text-sm font-bold text-on-surface">Penyulang Risiko Tertinggi</h4>
                        <p class="text-[11px] text-outline mt-0.5">Urutan berdasarkan RPN tertinggi · Tahun <?= $tahun ?></p>
                    </div>
                    <a href="<?= $baseUrl ?>/labeling?tahun=<?= $tahun ?>"
                       class="text-xs font-semibold text-primary hover:underline flex items-center gap-1">
                        Lihat semua <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                    </a>
                </div>

                <?php if (!empty($topHighRisk)): ?>
                <div class="overflow-x-auto -mx-1">
                    <table class="w-full text-left min-w-[520px]">
                        <thead>
                            <tr class="border-b border-outline-variant/40">
                                <?php foreach (['Penyulang','Bulan','S','O','D','RPN','Risiko'] as $h): ?>
                                <th class="pb-2.5 text-[10px] font-bold text-outline uppercase tracking-wider pr-4
                                           <?= in_array($h,['S','O','D','RPN']) ? 'text-center' : '' ?>"><?= $h ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/20">
                            <?php foreach ($topHighRisk as $row): ?>
                            <tr class="hover:bg-surface-container-low/60 transition-colors <?= $row['risk_label'] === 'Tinggi' ? 'bg-red-50/30' : '' ?>">
                                <td class="py-2.5 pr-4 text-sm font-semibold text-on-surface"><?= htmlspecialchars($row['penyulang']) ?></td>
                                <td class="py-2.5 pr-4 text-sm text-on-surface-variant"><?= $namaBulan[$row['bulan']] ?></td>
                                <td class="py-2.5 pr-4 text-sm text-center text-on-surface-variant"><?= $row['severity'] ?></td>
                                <td class="py-2.5 pr-4 text-sm text-center text-on-surface-variant"><?= $row['occurrence'] ?></td>
                                <td class="py-2.5 pr-4 text-sm text-center text-on-surface-variant"><?= $row['detection'] ?></td>
                                <td class="py-2.5 pr-4 text-sm font-bold text-center <?= $row['rpn'] >= 513 ? 'text-red-600' : ($row['rpn'] >= 126 ? 'text-amber-600' : 'text-green-600') ?>">
                                    <?= $row['rpn'] ?>
                                </td>
                                <td class="py-2.5">
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold <?= $riskBadge[$row['risk_label']] ?? 'bg-gray-100 text-gray-700' ?>">
                                        <?= $row['risk_label'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="py-10 text-center">
                    <span class="material-symbols-outlined text-[40px] text-outline/30 mb-2 block">table_rows</span>
                    <p class="text-sm text-outline">Belum ada data berlabel</p>
                    <a href="<?= $baseUrl ?>/labeling" class="mt-2 inline-block text-xs font-semibold text-primary hover:underline">Mulai labeling →</a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Right (1/3) ── -->
        <div class="space-y-6">

            <!-- Donut: distribusi risiko -->
            <div class="bg-surface-container-lowest rounded-xl shadow-sm p-5">
                <h4 class="text-sm font-bold text-on-surface mb-1">Distribusi Risiko</h4>
                <p class="text-[11px] text-outline mb-4"><?= $labeled ?> data berlabel · Tahun <?= $tahun ?></p>

                <?php if ($labeled > 0): ?>
                <div class="flex justify-center mb-4">
                    <div class="relative w-36 h-36">
                        <canvas id="donutChart"></canvas>
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                            <p class="text-2xl font-bold text-on-surface"><?= $labeled ?></p>
                            <p class="text-[10px] text-outline uppercase tracking-wider">Berlabel</p>
                        </div>
                    </div>
                </div>
                <div class="space-y-3">
                    <?php foreach ([
                        ['Rendah', $rendah, 'bg-green-500', 'text-green-700', 'bg-green-50'],
                        ['Sedang', $sedang, 'bg-amber-500', 'text-amber-700', 'bg-amber-50'],
                        ['Tinggi', $tinggi, 'bg-red-500',   'text-red-700',   'bg-red-50'],
                    ] as [$lbl, $cnt, $bar, $txt, $bg]): ?>
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full <?= $bar ?> flex-shrink-0"></span>
                                <span class="text-xs text-on-surface-variant"><?= $lbl ?></span>
                            </div>
                            <span class="text-[11px] font-bold px-1.5 py-0.5 rounded-full <?= $bg ?> <?= $txt ?>">
                                <?= $cnt ?> (<?= $pct($cnt) ?>%)
                            </span>
                        </div>
                        <div class="w-full h-1.5 bg-surface-container-high rounded-full overflow-hidden">
                            <div class="h-full <?= $bar ?> rounded-full" style="width:<?= $pct($cnt) ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-4 pt-3 border-t border-outline-variant/20 flex justify-between text-center">
                    <div>
                        <p class="text-[10px] text-outline uppercase tracking-wider">Avg RPN</p>
                        <p class="text-base font-bold text-amber-600"><?= $avgRpn ?></p>
                    </div>
                    <div>
                        <p class="text-[10px] text-outline uppercase tracking-wider">Max RPN</p>
                        <p class="text-base font-bold text-red-600"><?= $maxRpn ?></p>
                    </div>
                    <div>
                        <p class="text-[10px] text-outline uppercase tracking-wider">Penyulang</p>
                        <p class="text-base font-bold text-primary"><?= $penyulangCount ?></p>
                    </div>
                </div>
                <?php else: ?>
                <div class="py-8 text-center">
                    <span class="material-symbols-outlined text-[40px] text-outline/30 block mb-2">donut_large</span>
                    <p class="text-xs text-outline">Belum ada data berlabel</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Workflow Progress -->
            <div class="bg-surface-container-lowest rounded-xl shadow-sm p-5">
                <div class="flex justify-between items-center mb-4">
                    <h4 class="text-sm font-bold text-on-surface">Progres Alur Kerja</h4>
                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full
                                 <?= $workflowPct === 100 ? 'bg-green-100 text-green-700' : 'bg-primary/10 text-primary' ?>">
                        <?= $doneCount ?>/<?= count($workflow) ?>
                    </span>
                </div>

                <!-- Progress bar -->
                <div class="w-full h-1.5 bg-surface-container-high rounded-full overflow-hidden mb-4">
                    <div class="h-full bg-primary rounded-full transition-all duration-700" style="width:<?= $workflowPct ?>%"></div>
                </div>

                <ol class="space-y-2">
                    <?php foreach ($workflow as $i => $step): ?>
                    <li>
                        <a href="<?= $step['href'] ?>"
                           class="flex items-center gap-3 p-2 rounded-lg hover:bg-surface-container transition-colors group">
                            <!-- Step number / check -->
                            <div class="w-7 h-7 rounded-full flex-shrink-0 flex items-center justify-center text-xs font-bold
                                        <?= $step['done']
                                            ? 'bg-primary text-white'
                                            : 'bg-surface-container-high text-outline' ?>">
                                <?php if ($step['done']): ?>
                                <span class="material-symbols-outlined text-[14px]" style="font-variation-settings:'FILL' 1;">check</span>
                                <?php else: ?>
                                <?= $i + 1 ?>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold <?= $step['done'] ? 'text-on-surface' : 'text-on-surface-variant' ?>">
                                    <?= $step['label'] ?>
                                </p>
                                <p class="text-[10px] <?= $step['done'] ? 'text-outline' : 'text-outline/60' ?> truncate">
                                    <?= $step['info'] ?>
                                </p>
                            </div>
                            <span class="material-symbols-outlined text-[16px] text-outline/40 group-hover:text-primary transition-colors">chevron_right</span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ol>
            </div>

            <!-- KNN Model card / CTA -->
            <?php if ($latestModel): ?>
            <div class="bg-surface-container-lowest rounded-xl shadow-sm p-5">
                <div class="flex items-center gap-2 mb-3">
                    <span class="material-symbols-outlined text-primary text-[20px]" style="font-variation-settings:'FILL' 1;">model_training</span>
                    <h4 class="text-sm font-bold text-on-surface">Model KNN Aktif</h4>
                </div>
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <?php foreach ([
                        ['Akurasi',  round($latestModel['accuracy']        * 100, 1) . '%', 'text-teal-700'],
                        ['F1-Score', round($latestModel['f1_score']        * 100, 1) . '%', 'text-secondary'],
                        ['K Value',  $latestModel['k_value'],                               'text-primary'],
                        ['Data Uji', $latestModel['test_count'],                             'text-on-surface'],
                    ] as [$lbl, $val, $cls]): ?>
                    <div class="bg-surface-container-low rounded-lg p-3 text-center">
                        <p class="text-[10px] text-outline uppercase tracking-wider"><?= $lbl ?></p>
                        <p class="text-lg font-bold <?= $cls ?> mt-0.5"><?= $val ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="flex gap-2">
                    <a href="<?= $baseUrl ?>/knn/evaluate"
                       class="flex-1 text-center py-2 text-xs font-semibold rounded-lg bg-primary/10 text-primary hover:bg-primary/20 transition-colors">
                        Evaluasi
                    </a>
                    <a href="<?= $baseUrl ?>/knn/predict"
                       class="flex-1 text-center py-2 text-xs font-semibold rounded-lg bg-secondary/10 text-secondary hover:bg-secondary/20 transition-colors">
                        Prediksi
                    </a>
                </div>
            </div>

            <?php else: ?>
            <!-- No model CTA -->
            <div class="bg-primary rounded-xl p-5 text-white relative overflow-hidden">
                <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-white/10 rounded-full"></div>
                <span class="material-symbols-outlined text-[32px] mb-3 block opacity-80">model_training</span>
                <h4 class="text-sm font-bold mb-1">Belum ada model KNN</h4>
                <p class="text-[12px] text-white/80 mb-4">Latih model untuk mulai memprediksi risiko jaringan secara otomatis.</p>
                <a href="<?= $baseUrl ?>/knn/train"
                   class="block w-full text-center py-2 bg-white text-primary rounded-lg text-xs font-bold hover:bg-surface-container transition-colors relative z-10">
                    Latih Model Sekarang
                </a>
            </div>
            <?php endif; ?>

        </div><!-- end right -->
    </div><!-- end main grid -->

</main>

<?php require APP_PATH . '/Views/partials/toast.php'; ?>
<?php require APP_PATH . '/Views/partials/sidebar_script.php'; ?>

<?php if (!empty($chartLabels)): ?>
<script>
(function () {
    // Monthly stacked bar
    const mCtx = document.getElementById('monthlyChart');
    if (mCtx) {
        new Chart(mCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chartLabels) ?>,
                datasets: [
                    { label: 'Rendah', data: <?= json_encode($chartR) ?>, backgroundColor: 'rgba(34,197,94,0.8)',  borderRadius: 4 },
                    { label: 'Sedang', data: <?= json_encode($chartS) ?>, backgroundColor: 'rgba(245,158,11,0.8)', borderRadius: 4 },
                    { label: 'Tinggi', data: <?= json_encode($chartT) ?>, backgroundColor: 'rgba(239,68,68,0.8)',  borderRadius: 4 },
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    x: { stacked: true, grid: { display: false }, ticks: { color: '#6e7882', font: { size: 11 } } },
                    y: { stacked: true, beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { color: '#6e7882', font: { size: 11 }, stepSize: 1 } }
                }
            }
        });
    }
})();
</script>
<?php endif; ?>

<?php if ($labeled > 0): ?>
<script>
(function () {
    const dCtx = document.getElementById('donutChart');
    if (!dCtx) return;
    new Chart(dCtx, {
        type: 'doughnut',
        data: {
            labels: ['Rendah', 'Sedang', 'Tinggi'],
            datasets: [{
                data: [<?= $rendah ?>, <?= $sedang ?>, <?= $tinggi ?>],
                backgroundColor: ['rgba(34,197,94,0.85)', 'rgba(245,158,11,0.85)', 'rgba(239,68,68,0.85)'],
                borderWidth: 2,
                borderColor: '#fff',
                hoverOffset: 6,
            }]
        },
        options: {
            responsive: true,
            cutout: '68%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ` ${ctx.label}: ${ctx.raw} (${Math.round(ctx.raw / <?= $labeled ?> * 100)}%)`
                    }
                }
            }
        }
    });
})();
</script>
<?php endif; ?>
</body>
</html>
