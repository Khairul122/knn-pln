<?php
$flash      = Flash::get();
$baseUrl    = BASE_URL;
$userName   = htmlspecialchars($_SESSION['user_name']  ?? 'User');
$userEmail  = htmlspecialchars($_SESSION['user_email'] ?? '');
$userRole   = $_SESSION['user_role'] ?? 'viewer';

$pageHeadTitle = 'Laporan Risiko';
$activeMenu    = 'laporan';
$pageTitle     = 'Laporan Risiko';
$pageIcon      = 'report_problem';
$backUrl       = null;
$headerActions = null;

$namaBulan = ['','Januari','Februari','Maret','April','Mei','Juni',
              'Juli','Agustus','September','Oktober','November','Desember'];

$riskBdg  = ['Rendah'=>'bg-green-100 text-green-800','Sedang'=>'bg-amber-100 text-amber-800','Tinggi'=>'bg-red-100 text-red-800'];
$riskDot  = ['Rendah'=>'bg-green-500','Sedang'=>'bg-amber-500','Tinggi'=>'bg-red-500'];
$riskBig  = ['Rendah'=>'border-green-400 text-green-700','Sedang'=>'border-amber-400 text-amber-700','Tinggi'=>'border-red-400 text-red-700'];

$total    = (int)($summary['total_pemeliharaan'] ?? 0);
$labeled  = (int)($summary['total_labeled']      ?? 0);
$rendah   = (int)($summary['rendah']             ?? 0);
$sedang   = (int)($summary['sedang']             ?? 0);
$tinggi   = (int)($summary['tinggi']             ?? 0);
$avgRpn   = $summary['avg_rpn']  ?? 0;
$maxRpn   = $summary['max_rpn']  ?? 0;

// Monthly chart data (keyed by bulan 1-12)
$monthlyMap = [];
foreach ($monthly as $m) {
    $monthlyMap[(int)$m['bulan']] = $m;
}
$chartLabels  = [];
$chartRendah  = [];
$chartSedang  = [];
$chartTinggi  = [];
$chartAvgRpn  = [];
for ($b = 1; $b <= 12; $b++) {
    if (!isset($monthlyMap[$b])) continue;
    $chartLabels[] = substr($namaBulan[$b], 0, 3);
    $chartRendah[] = (int)($monthlyMap[$b]['rendah'] ?? 0);
    $chartSedang[] = (int)($monthlyMap[$b]['sedang'] ?? 0);
    $chartTinggi[] = (int)($monthlyMap[$b]['tinggi'] ?? 0);
    $chartAvgRpn[] = (float)($monthlyMap[$b]['avg_rpn'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php require APP_PATH . '/Views/partials/head.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        @media print {
            aside, header, .no-print { display: none !important; }
            main { margin: 0 !important; padding: 1rem !important; }
            .print-break { page-break-before: always; }
        }
    </style>
</head>
<body class="bg-background text-on-background">

<?php require APP_PATH . '/Views/partials/sidebar.php'; ?>
<?php require APP_PATH . '/Views/partials/header.php'; ?>

<main class="ml-0 md:ml-64 mt-16 p-4 sm:p-6 min-h-screen">

    <!-- Filter bar -->
    <div class="flex flex-wrap items-center gap-3 mb-6 bg-surface-container-lowest p-4 rounded-xl border border-outline-variant/30 shadow-sm no-print">
        <form method="GET" action="<?= $baseUrl ?>/laporan" class="flex flex-wrap items-center gap-3 flex-1">
            <label class="text-xs text-outline font-semibold">Tahun:</label>
            <select name="tahun" onchange="this.form.submit()"
                    class="text-sm border border-outline-variant rounded-lg px-3 py-1.5 bg-white text-on-surface focus:ring-1 focus:ring-primary outline-none">
                <?php foreach ($years as $y): ?>
                <option value="<?= $y ?>" <?= $y==$tahun?'selected':'' ?>><?= $y ?></option>
                <?php endforeach; ?>
            </select>

            <?php if (!empty($knnHistory)): ?>
            <label class="text-xs text-outline font-semibold">Model KNN:</label>
            <select name="model_id" onchange="this.form.submit()"
                    class="text-sm border border-outline-variant rounded-lg px-3 py-1.5 bg-white text-on-surface focus:ring-1 focus:ring-primary outline-none min-w-[220px]">
                <option value="">Tanpa prediksi KNN</option>
                <?php foreach ($knnHistory as $m):
                    $acc = $m['accuracy'] !== null ? ' · '.round($m['accuracy']*100,1).'%' : '';
                ?>
                <option value="<?= $m['id'] ?>" <?= ($selectedModel && $selectedModel['id']==$m['id']) ? 'selected':'' ?>>
                    Model #<?= $m['id'] ?> — K=<?= $m['k_value'] ?><?= $acc ?>
                </option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
        </form>
        <button onclick="window.print()"
                class="flex items-center gap-1.5 px-4 py-2 border border-outline-variant rounded-xl text-sm font-semibold text-on-surface-variant hover:bg-surface-container transition-colors">
            <span class="material-symbols-outlined text-[18px]">print</span> Cetak
        </button>
    </div>

    <!-- Report header (for print) -->
    <div class="mb-6">
        <h2 class="text-xl font-bold text-on-surface">Laporan Risiko Jaringan Distribusi Listrik</h2>
        <p class="text-sm text-outline mt-0.5">Tahun <?= $tahun ?> · Dibuat <?= date('d F Y') ?> · PLN GridRisk</p>
    </div>

    <!-- ① Summary Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <?php foreach ([
            ['Total Data',    $total,   'text-on-surface', 'border-outline-variant', 'dataset'],
            ['Berlabel',      $labeled, 'text-primary',    'border-primary',         'label'],
            ['Risiko Rendah', $rendah,  'text-green-700',  'border-green-400',       'check_circle'],
            ['Risiko Sedang', $sedang,  'text-amber-700',  'border-amber-400',       'warning'],
            ['Risiko Tinggi', $tinggi,  'text-red-700',    'border-red-400',         'dangerous'],
            ['Avg RPN',       $avgRpn,  'text-secondary',  'border-secondary',       'speed'],
        ] as [$lbl, $val, $cls, $border, $ico]): ?>
        <div class="bg-surface-container-lowest rounded-xl border-l-4 shadow-sm p-4 <?= $border ?>">
            <div class="flex items-center justify-between mb-1">
                <p class="text-[10px] font-semibold text-outline uppercase tracking-wider"><?= $lbl ?></p>
                <span class="material-symbols-outlined text-[16px] opacity-40 <?= $cls ?>"><?= $ico ?></span>
            </div>
            <p class="text-2xl font-bold <?= $cls ?>"><?= $val ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ② Charts row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

        <!-- Donut: Risk Distribution -->
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 shadow-sm p-5">
            <h3 class="text-sm font-semibold text-on-surface mb-1 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-[18px]">donut_large</span>
                Distribusi Risiko
            </h3>
            <p class="text-xs text-outline mb-4">Persentase dari <?= $labeled ?> data berlabel</p>
            <?php if ($labeled > 0): ?>
            <div class="h-52 flex items-center justify-center">
                <canvas id="donutChart"></canvas>
            </div>
            <div class="mt-3 space-y-1.5">
                <?php foreach (['Rendah'=>$rendah,'Sedang'=>$sedang,'Tinggi'=>$tinggi] as $lbl=>$cnt): ?>
                <div class="flex items-center justify-between text-xs">
                    <span class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full <?= $riskDot[$lbl] ?> inline-block"></span>
                        <span class="text-on-surface-variant"><?= $lbl ?></span>
                    </span>
                    <span class="font-semibold text-on-surface"><?= $cnt ?> <span class="text-outline font-normal">(<?= $labeled>0?round($cnt/$labeled*100,1):0 ?>%)</span></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="h-52 flex items-center justify-center text-outline text-sm">Belum ada data berlabel</div>
            <?php endif; ?>
        </div>

        <!-- Stacked Bar: Monthly Distribution (span 2) -->
        <div class="lg:col-span-2 bg-surface-container-lowest rounded-xl border border-outline-variant/30 shadow-sm p-5">
            <h3 class="text-sm font-semibold text-on-surface mb-1 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-[18px]">bar_chart</span>
                Distribusi Risiko per Bulan
            </h3>
            <p class="text-xs text-outline mb-4">Jumlah penyulang per kategori risiko tiap bulan — Tahun <?= $tahun ?></p>
            <?php if (!empty($chartLabels)): ?>
            <div class="h-52">
                <canvas id="monthlyChart"></canvas>
            </div>
            <?php else: ?>
            <div class="h-52 flex items-center justify-center text-outline text-sm">Tidak ada data bulanan</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ③ RPN Trend line -->
    <?php if (!empty($chartLabels)): ?>
    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 shadow-sm p-5 mb-8">
        <h3 class="text-sm font-semibold text-on-surface mb-1 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-[18px]">show_chart</span>
            Tren Rata-rata RPN per Bulan
        </h3>
        <p class="text-xs text-outline mb-4">Semakin tinggi RPN → semakin kritis risiko jaringan pada bulan tersebut</p>
        <div class="h-40">
            <canvas id="rpnChart"></canvas>
        </div>
    </div>
    <?php endif; ?>

    <!-- ④ High Risk Alerts -->
    <?php if (!empty($highRiskList)): ?>
    <div class="bg-red-50 border border-red-200 rounded-xl p-5 mb-8">
        <h3 class="text-sm font-bold text-red-800 mb-3 flex items-center gap-2">
            <span class="material-symbols-outlined text-red-600 text-[20px]" style="font-variation-settings:'FILL' 1;">emergency_heat</span>
            Peringatan Risiko Tinggi — <?= count($highRiskList) ?> Penyulang
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <?php foreach (array_slice($highRiskList, 0, 9) as $r): ?>
            <div class="bg-white rounded-xl border border-red-200 p-3">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="font-semibold text-sm text-on-surface truncate"><?= htmlspecialchars($r['penyulang']) ?></p>
                        <p class="text-xs text-outline"><?= $namaBulan[$r['bulan']] ?> <?= $tahun ?></p>
                        <p class="text-xs text-on-surface-variant mt-1 truncate" title="<?= htmlspecialchars($r['failure_mode']) ?>"><?= htmlspecialchars($r['failure_mode']) ?></p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-xl font-bold text-red-700"><?= $r['rpn'] ?></p>
                        <p class="text-[10px] text-red-500">RPN</p>
                        <p class="text-[10px] text-outline">S<?= $r['severity'] ?>·O<?= $r['occurrence'] ?>·D<?= $r['detection'] ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if (count($highRiskList) > 9): ?>
        <p class="text-xs text-red-600 mt-3 font-semibold">+ <?= count($highRiskList) - 9 ?> lainnya (lihat tabel detail di bawah)</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ⑤ Risk by Penyulang table -->
    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 shadow-sm overflow-hidden mb-8">
        <div class="px-5 py-4 border-b border-outline-variant/30 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-[18px]">power</span>
                Risiko per Penyulang
                <span class="text-xs text-outline font-normal">(<?= count($byPenyulang) ?> penyulang)</span>
            </h3>
            <div class="flex items-center gap-2 no-print">
                <input type="text" id="filterPenyulang" placeholder="Cari penyulang..."
                       class="text-sm border border-outline-variant rounded-lg px-3 py-1.5 outline-none focus:ring-1 focus:ring-primary"
                       oninput="filterTable(this.value)">
            </div>
        </div>
        <div class="overflow-x-auto max-h-96">
            <table class="w-full text-sm text-left min-w-[640px]" id="penyulangTable">
                <thead class="bg-surface-container-low sticky top-0 text-[10px] font-semibold text-outline uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Penyulang</th>
                        <th class="px-4 py-3 text-center">Risiko Dominan</th>
                        <th class="px-4 py-3 text-center">Tinggi</th>
                        <th class="px-4 py-3 text-center">Sedang</th>
                        <th class="px-4 py-3 text-center">Rendah</th>
                        <th class="px-4 py-3 text-center">Max RPN</th>
                        <th class="px-4 py-3 text-center">Avg RPN</th>
                        <th class="px-4 py-3 text-center">Bulan Aktif</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20" id="penyulangBody">
                    <?php foreach ($byPenyulang as $i => $row):
                        $dom = $row['dominant_risk'] ?? 'Rendah';
                    ?>
                    <tr class="hover:bg-surface-container-low/40 transition-colors penyulang-row
                               <?= $row['tinggi'] > 0 ? 'bg-red-50/30' : '' ?>">
                        <td class="px-4 py-2.5 text-xs text-outline"><?= $i+1 ?></td>
                        <td class="px-4 py-2.5 font-semibold text-on-surface"><?= htmlspecialchars($row['penyulang']) ?></td>
                        <td class="px-4 py-2.5 text-center">
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= $riskBdg[$dom] ?? '' ?>"><?= $dom ?></span>
                        </td>
                        <td class="px-4 py-2.5 text-center">
                            <?php if ($row['tinggi'] > 0): ?>
                            <span class="font-bold text-red-700"><?= $row['tinggi'] ?></span>
                            <?php else: ?><span class="text-outline">—</span><?php endif; ?>
                        </td>
                        <td class="px-4 py-2.5 text-center">
                            <?php if ($row['sedang'] > 0): ?>
                            <span class="font-semibold text-amber-700"><?= $row['sedang'] ?></span>
                            <?php else: ?><span class="text-outline">—</span><?php endif; ?>
                        </td>
                        <td class="px-4 py-2.5 text-center">
                            <?php if ($row['rendah'] > 0): ?>
                            <span class="font-semibold text-green-700"><?= $row['rendah'] ?></span>
                            <?php else: ?><span class="text-outline">—</span><?php endif; ?>
                        </td>
                        <td class="px-4 py-2.5 text-center">
                            <?php $rpnCls = $row['max_rpn']>200?'text-red-700 bg-red-50':($row['max_rpn']>100?'text-amber-700 bg-amber-50':'text-green-700 bg-green-50'); ?>
                            <span class="px-2 py-0.5 rounded-md text-xs font-bold <?= $rpnCls ?>"><?= $row['max_rpn'] ?></span>
                        </td>
                        <td class="px-4 py-2.5 text-center text-xs text-on-surface-variant"><?= $row['avg_rpn'] ?></td>
                        <td class="px-4 py-2.5 text-center text-xs text-outline"><?= $row['total_bulan'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ⑥ KNN vs FMEA Disagreements -->
    <?php if ($selectedModel && !empty($disagreements)): ?>
    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 shadow-sm overflow-hidden mb-8">
        <div class="px-5 py-4 border-b border-outline-variant/30 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-amber-600 text-[18px]">compare_arrows</span>
                Perbedaan KNN vs Label FMEA
                <span class="text-xs text-outline font-normal">(<?= count($disagreements) ?> ketidaksesuaian)</span>
            </h3>
            <span class="text-xs text-outline bg-amber-50 border border-amber-200 px-3 py-1 rounded-full">
                Model #<?= $selectedModel['id'] ?> · K=<?= $selectedModel['k_value'] ?>
            </span>
        </div>
        <div class="overflow-x-auto max-h-72">
            <table class="w-full text-sm text-left min-w-[560px]">
                <thead class="bg-surface-container-low sticky top-0 text-[10px] font-semibold text-outline uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Penyulang</th>
                        <th class="px-4 py-3 text-center">Bulan</th>
                        <th class="px-4 py-3 text-center">RPN</th>
                        <th class="px-4 py-3 text-center">Label FMEA</th>
                        <th class="px-4 py-3 text-center">Prediksi KNN</th>
                        <th class="px-4 py-3 text-center">Conf.</th>
                        <th class="px-4 py-3 text-center">Selisih</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    <?php
                    $riskRank = ['Rendah'=>1,'Sedang'=>2,'Tinggi'=>3];
                    foreach ($disagreements as $i => $row):
                        $fRank = $riskRank[$row['fmea_label']] ?? 1;
                        $kRank = $riskRank[$row['knn_label']]  ?? 1;
                        $diff  = $kRank - $fRank;
                        $diffLbl = $diff > 0 ? '↑ KNN lebih tinggi' : '↓ KNN lebih rendah';
                        $diffCls = $diff > 0 ? 'text-red-600 bg-red-50' : 'text-blue-600 bg-blue-50';
                    ?>
                    <tr class="hover:bg-surface-container-low/40 transition-colors">
                        <td class="px-4 py-2.5 text-xs text-outline"><?= $i+1 ?></td>
                        <td class="px-4 py-2.5 font-semibold text-sm text-on-surface"><?= htmlspecialchars($row['penyulang']) ?></td>
                        <td class="px-4 py-2.5 text-center text-xs text-on-surface-variant"><?= $namaBulan[$row['bulan']] ?></td>
                        <td class="px-4 py-2.5 text-center">
                            <?php $rpnCls = $row['rpn']>200?'text-red-700 bg-red-50':($row['rpn']>100?'text-amber-700 bg-amber-50':'text-green-700 bg-green-50'); ?>
                            <span class="px-2 py-0.5 rounded-md text-xs font-bold <?= $rpnCls ?>"><?= $row['rpn'] ?></span>
                        </td>
                        <td class="px-4 py-2.5 text-center">
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= $riskBdg[$row['fmea_label']] ?? '' ?>"><?= $row['fmea_label'] ?></span>
                        </td>
                        <td class="px-4 py-2.5 text-center">
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= $riskBdg[$row['knn_label']] ?? '' ?> ring-1 ring-offset-1 ring-current"><?= $row['knn_label'] ?></span>
                        </td>
                        <td class="px-4 py-2.5 text-center text-xs text-outline"><?= round($row['confidence']*100) ?>%</td>
                        <td class="px-4 py-2.5 text-center">
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold <?= $diffCls ?>"><?= $diffLbl ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 border-t border-outline-variant/20 bg-amber-50/50">
            <p class="text-[11px] text-amber-800">
                <strong>Catatan:</strong>
                "KNN lebih tinggi" berarti model menilai risiko lebih serius dari label manual — perlu verifikasi ulang.
                "KNN lebih rendah" berarti label manual mungkin terlalu konservatif.
            </p>
        </div>
    </div>
    <?php elseif ($selectedModel): ?>
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-8 flex items-center gap-3">
        <span class="material-symbols-outlined text-green-600 text-[22px]">check_circle</span>
        <p class="text-sm text-green-800">
            <strong>KNN dan Label FMEA sepenuhnya konsisten</strong> — tidak ada perbedaan prediksi untuk model #<?= $selectedModel['id'] ?>.
        </p>
    </div>
    <?php endif; ?>

    <!-- ⑦ Detail Table -->
    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 shadow-sm overflow-hidden print-break">
        <div class="px-5 py-4 border-b border-outline-variant/30">
            <h3 class="text-sm font-semibold text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-[18px]">table_chart</span>
                Data Lengkap Labeling FMEA
                <?= $selectedModel ? '<span class="text-xs font-normal text-outline ml-1">+ Prediksi KNN Model #'.$selectedModel['id'].'</span>' : '' ?>
            </h3>
        </div>
        <div class="overflow-x-auto max-h-[480px]">
            <table class="w-full text-sm text-left min-w-[<?= $selectedModel ? '900' : '780' ?>px]">
                <thead class="bg-surface-container-low sticky top-0 text-[10px] font-semibold text-outline uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Penyulang</th>
                        <th class="px-4 py-3 text-center">Bulan</th>
                        <th class="px-4 py-3 text-center">S</th>
                        <th class="px-4 py-3 text-center">O</th>
                        <th class="px-4 py-3 text-center">D</th>
                        <th class="px-4 py-3 text-center">RPN</th>
                        <th class="px-4 py-3">Mode Kegagalan</th>
                        <th class="px-4 py-3 text-center">Risk Label</th>
                        <?php if ($selectedModel): ?>
                        <th class="px-4 py-3 text-center">Prediksi KNN</th>
                        <th class="px-4 py-3 text-center">Conf.</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    <?php foreach ($detail as $i => $row):
                        $match = $selectedModel && $row['predicted_label']
                                 ? ($row['risk_label'] === $row['predicted_label'])
                                 : null;
                    ?>
                    <tr class="hover:bg-surface-container-low/40 transition-colors
                               <?= $row['risk_label']==='Tinggi' ? 'bg-red-50/20' : '' ?>">
                        <td class="px-4 py-2.5 text-xs text-outline"><?= $i+1 ?></td>
                        <td class="px-4 py-2.5 font-semibold text-sm text-on-surface"><?= htmlspecialchars($row['penyulang']) ?></td>
                        <td class="px-4 py-2.5 text-center text-xs text-on-surface-variant"><?= $namaBulan[$row['bulan']] ?></td>
                        <td class="px-4 py-2.5 text-center text-xs font-semibold"><?= $row['severity'] ?></td>
                        <td class="px-4 py-2.5 text-center text-xs font-semibold"><?= $row['occurrence'] ?></td>
                        <td class="px-4 py-2.5 text-center text-xs font-semibold"><?= $row['detection'] ?></td>
                        <td class="px-4 py-2.5 text-center">
                            <?php $rpnCls = $row['rpn']>200?'text-red-700 bg-red-50':($row['rpn']>100?'text-amber-700 bg-amber-50':'text-green-700 bg-green-50'); ?>
                            <span class="px-2 py-0.5 rounded-md text-xs font-bold <?= $rpnCls ?>"><?= $row['rpn'] ?></span>
                        </td>
                        <td class="px-4 py-2.5 text-xs text-on-surface-variant max-w-[160px] truncate" title="<?= htmlspecialchars($row['failure_mode']) ?>">
                            <?= htmlspecialchars($row['failure_mode']) ?>
                        </td>
                        <td class="px-4 py-2.5 text-center">
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= $riskBdg[$row['risk_label']] ?? '' ?>"><?= $row['risk_label'] ?></span>
                        </td>
                        <?php if ($selectedModel): ?>
                        <td class="px-4 py-2.5 text-center">
                            <?php if ($row['predicted_label']): ?>
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= $riskBdg[$row['predicted_label']] ?? '' ?>
                                         <?= $match===false ? 'ring-1 ring-red-400' : '' ?>">
                                <?= $row['predicted_label'] ?>
                            </span>
                            <?php else: ?><span class="text-outline text-xs">—</span><?php endif; ?>
                        </td>
                        <td class="px-4 py-2.5 text-center text-xs">
                            <?php if ($row['confidence']): ?>
                            <span class="<?= $match ? 'text-green-600' : ($match===false ? 'text-red-500' : 'text-outline') ?>">
                                <?= round($row['confidence']*100) ?>%
                            </span>
                            <?php else: ?><span class="text-outline">—</span><?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<?php require APP_PATH . '/Views/partials/toast.php'; ?>
<?php require APP_PATH . '/Views/partials/sidebar_script.php'; ?>
<script>
// ── Charts ──────────────────────────────────────────────────────────────────
<?php if ($labeled > 0): ?>
new Chart(document.getElementById('donutChart'), {
    type: 'doughnut',
    data: {
        labels: ['Rendah','Sedang','Tinggi'],
        datasets: [{
            data: [<?= $rendah ?>, <?= $sedang ?>, <?= $tinggi ?>],
            backgroundColor: ['#22c55e','#f59e0b','#ef4444'],
            borderWidth: 2,
            borderColor: '#fff',
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '68%',
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => ' ' + ctx.label + ': ' + ctx.parsed + ' data (' + Math.round(ctx.parsed/<?= $labeled ?>*100) + '%)'
                }
            }
        }
    }
});
<?php endif; ?>

<?php if (!empty($chartLabels)): ?>
new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [
            { label:'Rendah', data: <?= json_encode($chartRendah) ?>, backgroundColor:'rgba(34,197,94,0.8)',  borderRadius:4 },
            { label:'Sedang', data: <?= json_encode($chartSedang) ?>, backgroundColor:'rgba(245,158,11,0.8)', borderRadius:4 },
            { label:'Tinggi', data: <?= json_encode($chartTinggi) ?>, backgroundColor:'rgba(239,68,68,0.8)',  borderRadius:4 },
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top', labels: { font: { size:11 }, padding:12, usePointStyle:true } },
            tooltip: { mode:'index', intersect:false }
        },
        scales: {
            x: { stacked:true, ticks:{ font:{size:11} }, grid:{ display:false } },
            y: { stacked:true, ticks:{ font:{size:11}, stepSize:1 }, grid:{ color:'rgba(0,0,0,0.05)' } }
        }
    }
});

new Chart(document.getElementById('rpnChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
            label: 'Avg RPN',
            data: <?= json_encode($chartAvgRpn) ?>,
            fill: true,
            tension: 0.4,
            borderColor: '#006492',
            backgroundColor: 'rgba(0,100,146,0.08)',
            pointBackgroundColor: <?= json_encode(array_map(fn($v) => $v > 200 ? '#ef4444' : ($v > 100 ? '#f59e0b' : '#22c55e'), $chartAvgRpn)) ?>,
            pointRadius: 5,
            pointBorderWidth: 2,
            pointBorderColor: '#fff',
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display:false },
            tooltip: {
                callbacks: {
                    label: ctx => ' Avg RPN: ' + ctx.parsed.y,
                    afterLabel: ctx => ctx.parsed.y > 200 ? '⚠ Risiko Tinggi' : (ctx.parsed.y > 100 ? '⚠ Risiko Sedang' : '✓ Risiko Rendah')
                }
            }
        },
        scales: {
            y: {
                ticks: { font:{size:11} },
                grid: { color:'rgba(0,0,0,0.05)' },
                suggestedMin: 0
            },
            x: { ticks:{ font:{size:11} }, grid:{ display:false } }
        }
    }
});
<?php endif; ?>

// ── Filter penyulang table ───────────────────────────────────────────────────
function filterTable(q) {
    q = q.toLowerCase();
    document.querySelectorAll('.penyulang-row').forEach(row => {
        const name = row.cells[1]?.textContent.toLowerCase() ?? '';
        row.style.display = name.includes(q) ? '' : 'none';
    });
}
</script>
</body>
</html>
