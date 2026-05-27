<?php
$flash      = Flash::get();
$baseUrl    = BASE_URL;
$userName   = htmlspecialchars($_SESSION['user_name']  ?? 'User');
$userEmail  = htmlspecialchars($_SESSION['user_email'] ?? '');
$userRole   = $_SESSION['user_role'] ?? 'viewer';

$pageHeadTitle = 'Evaluasi Model KNN';
$activeMenu    = 'knn';
$pageTitle     = 'Prediksi KNN';
$pageIcon      = 'analytics';
$backUrl       = null;
$headerActions = null;

$classes   = ['Rendah', 'Sedang', 'Tinggi'];
$namaBulan = ['','Januari','Februari','Maret','April','Mei','Juni',
              'Juli','Agustus','September','Oktober','November','Desember'];
$riskBdg   = [
    'Rendah' => 'bg-green-100 text-green-800',
    'Sedang' => 'bg-amber-100 text-amber-800',
    'Tinggi' => 'bg-red-100   text-red-800',
];
$riskColor = ['Rendah' => '#22c55e', 'Sedang' => '#f59e0b', 'Tinggi' => '#ef4444'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php require APP_PATH . '/Views/partials/head.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .cm-cell { transition: transform .15s; }
        .cm-cell:hover { transform: scale(1.05); z-index: 10; position: relative; }
    </style>
</head>
<body class="bg-background text-on-background">

<?php require APP_PATH . '/Views/partials/sidebar.php'; ?>
<?php require APP_PATH . '/Views/partials/header.php'; ?>

<main class="ml-0 md:ml-64 mt-16 p-4 sm:p-6 min-h-screen">

    <!-- KNN Tab Navigation -->
    <div class="flex gap-1 bg-surface-container p-1 rounded-xl mb-6 w-fit">
        <?php foreach ([
            ['train',    'Training',       'model_training'],
            ['evaluate', 'Evaluasi Model', 'analytics'],
            ['predict',  'Prediksi',       'scatter_plot'],
        ] as [$key, $lbl, $ico]): $active = ($key === 'evaluate'); ?>
        <a href="<?= $baseUrl ?>/knn/<?= $key ?>?tahun=<?= $tahun ?><?= ($selected && $key === 'evaluate') ? '&model_id='.$selected['id'] : '' ?>"
           class="flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold transition-colors
                  <?= $active ? 'bg-white text-primary shadow-sm' : 'text-on-surface-variant hover:text-on-surface' ?>">
            <span class="material-symbols-outlined text-[16px]"><?= $ico ?></span>
            <?= $lbl ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Model selector bar -->
    <div class="flex flex-wrap items-center gap-3 mb-6 bg-surface-container-lowest p-4 rounded-xl border border-outline-variant/30 shadow-sm">
        <form method="GET" action="<?= $baseUrl ?>/knn/evaluate" class="flex flex-wrap items-center gap-3 flex-1">
            <label class="text-xs text-outline font-semibold whitespace-nowrap">Pilih Model:</label>
            <select name="model_id" onchange="this.form.submit()"
                    class="text-sm border border-outline-variant rounded-lg px-3 py-1.5 bg-white text-on-surface focus:ring-1 focus:ring-primary outline-none flex-1 min-w-[260px]">
                <option value="">-- Pilih Model --</option>
                <?php foreach ($history as $m):
                    $accTxt = $m['accuracy'] !== null ? ' · Akurasi '.round($m['accuracy']*100,1).'%' : '';
                ?>
                <option value="<?= $m['id'] ?>" <?= ($selected && $selected['id'] == $m['id']) ? 'selected' : '' ?>>
                    Model #<?= $m['id'] ?> — K=<?= $m['k_value'] ?>, <?= ucfirst($m['distance_metric']) ?>, <?= date('d M Y H:i', strtotime($m['trained_at'])) ?><?= $accTxt ?>
                </option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="tahun" value="<?= $tahun ?>">
            <select name="tahun" onchange="this.form.submit()"
                    class="text-sm border border-outline-variant rounded-lg px-3 py-1.5 bg-white text-on-surface focus:ring-1 focus:ring-primary outline-none">
                <?php foreach ($years as $y): ?>
                <option value="<?= $y ?>" <?= $y == $tahun ? 'selected' : '' ?>><?= $y ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php if ($selected): ?>
        <a href="<?= $baseUrl ?>/knn/predict?model_id=<?= $selected['id'] ?>&tahun=<?= $tahun ?>"
           class="flex items-center gap-1.5 px-4 py-2 bg-secondary text-white rounded-xl text-sm font-semibold hover:opacity-90 transition-opacity whitespace-nowrap">
            <span class="material-symbols-outlined text-[16px]">scatter_plot</span> Prediksi
        </a>
        <?php endif; ?>
    </div>

    <?php if (!$selected): ?>
    <!-- No model -->
    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 p-14 text-center">
        <span class="material-symbols-outlined text-[56px] text-outline/30 block mb-3">model_training</span>
        <p class="font-semibold text-on-surface mb-1">Belum ada model terlatih</p>
        <p class="text-sm text-outline mb-4">Latih model KNN terlebih dahulu di tab Training.</p>
        <a href="<?= $baseUrl ?>/knn/train?tahun=<?= $tahun ?>"
           class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary text-white rounded-xl text-sm font-semibold hover:bg-on-primary-fixed-variant transition-colors">
            <span class="material-symbols-outlined text-[16px]">model_training</span> Latih Model
        </a>
    </div>

    <?php elseif (!$evalResult): ?>
    <!-- Has model, no test data -->
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 mb-5 flex items-start gap-3">
        <span class="material-symbols-outlined text-amber-500 text-[22px] mt-0.5">warning</span>
        <div>
            <p class="text-sm font-semibold text-amber-800">Data test tidak tersedia</p>
            <p class="text-xs text-amber-700 mt-0.5">Model dilatih dengan <?= $selected['train_count'] ?> data train, tetapi tidak ada data test (split_type=&#39;test&#39;). Lakukan split data di halaman Labeling → Split Data.</p>
        </div>
    </div>
    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 shadow-sm p-5">
        <h3 class="text-sm font-semibold text-on-surface mb-3">Info Model #<?= $selected['id'] ?></h3>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <?php foreach ([['K',$selected['k_value']],['Metrik',ucfirst($selected['distance_metric'])],['Fitur',str_replace(',',', ',$selected['feature_columns'])],['Train',$selected['train_count']]] as [$l,$v]): ?>
            <div class="p-3 bg-surface-container rounded-xl">
                <p class="text-[10px] text-outline font-semibold uppercase"><?= $l ?></p>
                <p class="text-sm font-semibold text-on-surface mt-0.5"><?= $v ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php else: ?>
    <?php
    /* ── Precompute metrics ─────────────────────────────────────────────── */
    $cm  = $evalResult['conf_matrix'];
    $acc = round($evalResult['accuracy']        * 100, 2);
    $pre = round($evalResult['macro_precision'] * 100, 2);
    $rec = round($evalResult['macro_recall']    * 100, 2);
    $f1  = round($evalResult['macro_f1']        * 100, 2);

    // Max value in CM for intensity scaling
    $cmMax = 0;
    foreach ($classes as $a) foreach ($classes as $b) $cmMax = max($cmMax, $cm[$a][$b]);

    // Row totals and col totals
    $rowTotals = [];
    $colTotals = [];
    foreach ($classes as $a) {
        $rowTotals[$a] = array_sum($cm[$a]);
        $colTotals[$a] = array_sum(array_column($cm, $a));
    }
    $grandTotal = array_sum($rowTotals);

    $accentFn = fn($v) => $v >= 80 ? 'text-green-700 border-green-400' : ($v >= 60 ? 'text-amber-700 border-amber-400' : 'text-red-700 border-red-400');
    ?>

    <!-- Model strip -->
    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 shadow-sm px-5 py-3 mb-6 flex flex-wrap items-center gap-4 text-xs text-on-surface-variant">
        <span><strong class="text-on-surface">Model #<?= $selected['id'] ?></strong></span>
        <span>K = <strong class="text-primary"><?= $selected['k_value'] ?></strong></span>
        <span>Metrik: <strong><?= ucfirst($selected['distance_metric']) ?></strong></span>
        <span>Fitur: <strong><?= str_replace(',', ', ', $selected['feature_columns']) ?></strong></span>
        <span>Train: <strong><?= $selected['train_count'] ?></strong> · Test: <strong><?= $evalResult['n_total'] ?></strong></span>
        <span>Dilatih: <strong><?= date('d M Y H:i', strtotime($selected['trained_at'])) ?></strong></span>
    </div>

    <!-- ① Metric cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <?php foreach ([
            ['Akurasi',   $acc, 'check_circle',             $evalResult['n_correct'].'/'.$evalResult['n_total'].' data benar'],
            ['Presisi',   $pre, 'precision_manufacturing',  'Macro-average'],
            ['Recall',    $rec, 'restore',                  'Macro-average'],
            ['F1-Score',  $f1,  'balance',                  'Macro-average'],
        ] as [$lbl, $val, $ico, $sub]): ?>
        <div class="bg-surface-container-lowest rounded-xl border-l-4 shadow-sm p-5 <?= $accentFn($val) ?>">
            <div class="flex items-center justify-between mb-2">
                <p class="text-[10px] font-semibold text-outline uppercase tracking-wider"><?= $lbl ?></p>
                <span class="material-symbols-outlined text-[20px] opacity-50"><?= $ico ?></span>
            </div>
            <p class="text-3xl font-bold mb-1"><?= $val ?>%</p>
            <p class="text-[10px] text-outline"><?= $sub ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ② Confusion Matrix — full-width prominent card -->
    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 shadow-sm p-6 mb-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-base font-bold text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-[22px]" style="font-variation-settings:'FILL' 1;">grid_on</span>
                    Confusion Matrix
                </h3>
                <p class="text-xs text-outline mt-0.5">Baris = label aktual · Kolom = label prediksi KNN · Diagonal = prediksi benar</p>
            </div>
            <div class="flex items-center gap-3 text-xs text-outline">
                <span class="flex items-center gap-1.5">
                    <span class="w-4 h-4 rounded bg-primary/25 inline-block border border-primary/30"></span> Benar (TP)
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-4 h-4 rounded bg-red-200 inline-block border border-red-300"></span> Salah
                </span>
            </div>
        </div>

        <div class="flex gap-6 items-start flex-wrap">

            <!-- Matrix grid -->
            <div class="flex-1 min-w-[320px]">
                <!-- Column headers (Predicted) -->
                <div class="flex items-center mb-2">
                    <div class="w-28 flex-shrink-0"></div>
                    <div class="flex-1 text-center text-xs font-bold text-on-surface-variant mb-1">
                        ← Prediksi KNN →
                    </div>
                </div>
                <div class="flex items-center mb-1">
                    <div class="w-28 flex-shrink-0"></div>
                    <?php foreach ($classes as $cls): ?>
                    <div class="flex-1 text-center">
                        <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold <?= $riskBdg[$cls] ?>"><?= $cls ?></span>
                    </div>
                    <?php endforeach; ?>
                    <div class="w-12 text-center text-[10px] text-outline font-semibold uppercase">Total</div>
                </div>

                <!-- Rows (Actual) -->
                <?php foreach ($classes as $actual): ?>
                <div class="flex items-center mb-2">
                    <!-- Row label (Actual) -->
                    <div class="w-28 flex-shrink-0 flex items-center justify-end pr-3">
                        <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold <?= $riskBdg[$actual] ?>"><?= $actual ?></span>
                    </div>

                    <!-- Cells -->
                    <?php foreach ($classes as $predicted):
                        $val     = $cm[$actual][$predicted];
                        $isDiag  = ($actual === $predicted);
                        $pct     = $rowTotals[$actual] > 0 ? round($val / $rowTotals[$actual] * 100) : 0;
                        $opacity = $cmMax > 0 ? $val / $cmMax : 0;

                        if ($isDiag) {
                            $intensity = max(0.12, $opacity);
                            $bgStyle   = "background: rgba(0,100,146,{$intensity})";
                            $border    = 'border-2 border-primary/40';
                            $numCls    = $val > 0 ? 'text-primary font-extrabold' : 'text-outline/50 font-normal';
                        } else {
                            if ($val === 0) {
                                $bgStyle = 'background: rgba(0,0,0,0.03)';
                                $border  = 'border border-outline-variant/20';
                                $numCls  = 'text-outline/30 font-normal';
                            } else {
                                $intensity = min(0.75, $opacity * 0.7 + 0.15);
                                $bgStyle   = "background: rgba(239,68,68,{$intensity})";
                                $border    = 'border border-red-300';
                                $numCls    = 'text-red-900 font-bold';
                            }
                        }
                    ?>
                    <div class="flex-1 px-1">
                        <div class="cm-cell rounded-xl <?= $border ?> flex flex-col items-center justify-center h-20 cursor-default"
                             style="<?= $bgStyle ?>"
                             title="Aktual: <?= $actual ?> → Prediksi: <?= $predicted ?> = <?= $val ?> data (<?= $pct ?>%)">
                            <span class="text-2xl <?= $numCls ?>"><?= $val ?></span>
                            <?php if ($rowTotals[$actual] > 0): ?>
                            <span class="text-[10px] <?= $isDiag && $val>0 ? 'text-primary/70' : 'text-outline' ?> mt-0.5"><?= $pct ?>%</span>
                            <?php endif; ?>
                            <?php if ($isDiag && $val > 0): ?>
                            <span class="text-[9px] text-primary/60 font-semibold">TP</span>
                            <?php elseif (!$isDiag && $val > 0): ?>
                            <span class="text-[9px] text-red-400 font-semibold">ERR</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <!-- Row total -->
                    <div class="w-12 text-center text-xs font-bold text-on-surface-variant">
                        <?= $rowTotals[$actual] ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Column totals row -->
                <div class="flex items-center mt-1">
                    <div class="w-28 flex-shrink-0 pr-3 text-right text-[10px] text-outline font-semibold uppercase">Total</div>
                    <?php foreach ($classes as $cls): ?>
                    <div class="flex-1 text-center text-xs font-bold text-on-surface-variant"><?= $colTotals[$cls] ?></div>
                    <?php endforeach; ?>
                    <div class="w-12 text-center text-xs font-bold text-primary"><?= $grandTotal ?></div>
                </div>
            </div>

            <!-- Side stats -->
            <div class="w-52 flex-shrink-0 space-y-3">
                <div class="bg-surface-container rounded-xl p-4">
                    <p class="text-[10px] text-outline font-semibold uppercase tracking-wider mb-3">Per Kelas</p>
                    <?php foreach ($classes as $cls):
                        $tp    = $cm[$cls][$cls];
                        $total = $rowTotals[$cls];
                        $pct   = $total > 0 ? round($tp/$total*100) : 0;
                        $barCls = $cls==='Rendah'?'bg-green-500':($cls==='Sedang'?'bg-amber-500':'bg-red-500');
                    ?>
                    <div class="mb-3 last:mb-0">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-semibold text-on-surface"><?= $cls ?></span>
                            <span class="text-xs text-outline"><?= $tp ?>/<?= $total ?></span>
                        </div>
                        <div class="h-2 bg-outline/10 rounded-full overflow-hidden">
                            <div class="<?= $barCls ?> h-full rounded-full" style="width:<?= $pct ?>%"></div>
                        </div>
                        <p class="text-[10px] text-outline text-right mt-0.5"><?= $pct ?>% benar</p>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="bg-primary/5 border border-primary/15 rounded-xl p-4">
                    <p class="text-[10px] text-outline font-semibold uppercase tracking-wider mb-2">Total Akurasi</p>
                    <?php $correct = array_sum(array_map(fn($c) => $cm[$c][$c], $classes)); ?>
                    <p class="text-3xl font-bold text-primary"><?= $acc ?>%</p>
                    <p class="text-xs text-outline mt-1"><?= $correct ?> benar dari <?= $grandTotal ?> data test</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ③ Classification report + K-curve side by side -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

        <!-- Per-class metrics table -->
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 shadow-sm p-5">
            <h3 class="text-sm font-semibold text-on-surface mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-[18px]">table_chart</span>
                Laporan Klasifikasi
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-[10px] font-semibold text-outline uppercase tracking-wider border-b border-outline-variant/30">
                            <th class="pb-2.5 text-left">Kelas</th>
                            <th class="pb-2.5 text-right">Presisi</th>
                            <th class="pb-2.5 text-right">Recall</th>
                            <th class="pb-2.5 text-right">F1</th>
                            <th class="pb-2.5 text-right">Support</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/15">
                        <?php foreach ($classes as $cls):
                            $m   = $evalResult['class_metrics'][$cls];
                            $pct = fn($v) => round($v * 100, 1) . '%';
                        ?>
                        <tr>
                            <td class="py-3">
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= $riskBdg[$cls] ?>"><?= $cls ?></span>
                            </td>
                            <td class="py-3 text-right">
                                <span class="font-semibold text-on-surface text-xs"><?= $pct($m['p']) ?></span>
                            </td>
                            <td class="py-3 text-right">
                                <span class="font-semibold text-on-surface text-xs"><?= $pct($m['r']) ?></span>
                            </td>
                            <td class="py-3 text-right">
                                <span class="font-semibold text-on-surface text-xs"><?= $pct($m['f1']) ?></span>
                            </td>
                            <td class="py-3 text-right text-xs text-outline"><?= $m['sup'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="border-t-2 border-outline-variant/40">
                        <tr>
                            <td class="py-3 text-xs font-bold text-outline">Macro Avg</td>
                            <td class="py-3 text-right text-xs font-bold text-primary"><?= round($evalResult['macro_precision']*100,1) ?>%</td>
                            <td class="py-3 text-right text-xs font-bold text-primary"><?= round($evalResult['macro_recall']*100,1) ?>%</td>
                            <td class="py-3 text-right text-xs font-bold text-primary"><?= round($evalResult['macro_f1']*100,1) ?>%</td>
                            <td class="py-3 text-right text-xs text-outline"><?= $evalResult['n_total'] ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <!-- F1 bars -->
            <div class="mt-5 space-y-2 pt-4 border-t border-outline-variant/20">
                <p class="text-[10px] text-outline font-semibold uppercase tracking-wider mb-2">F1-Score per Kelas</p>
                <?php foreach ($classes as $cls):
                    $f1v    = round($evalResult['class_metrics'][$cls]['f1'] * 100, 1);
                    $barCls = $cls==='Rendah'?'bg-green-500':($cls==='Sedang'?'bg-amber-500':'bg-red-500');
                ?>
                <div class="flex items-center gap-2">
                    <span class="w-16 text-right text-[10px] px-2 py-0.5 rounded-full font-semibold <?= $riskBdg[$cls] ?> flex-shrink-0"><?= $cls ?></span>
                    <div class="flex-1 h-3 bg-outline/10 rounded-full overflow-hidden">
                        <div class="<?= $barCls ?> h-full rounded-full transition-all" style="width:<?= $f1v ?>%"></div>
                    </div>
                    <span class="text-xs font-semibold text-on-surface w-10 text-right"><?= $f1v ?>%</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- K-accuracy curve -->
        <?php if (!empty($kAccuracies)): ?>
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 shadow-sm p-5">
            <h3 class="text-sm font-semibold text-on-surface mb-1 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-[18px]">show_chart</span>
                Kurva Akurasi vs Nilai K
            </h3>
            <p class="text-xs text-outline mb-4">Titik biru = K yang digunakan model saat ini</p>
            <div class="h-60">
                <canvas id="kChart"></canvas>
            </div>
            <?php
            $bestK   = array_search(max($kAccuracies), $kAccuracies);
            $bestAcc = max($kAccuracies);
            ?>
            <div class="mt-4 flex items-center gap-3 text-xs">
                <div class="flex items-center gap-1.5 bg-primary/5 px-3 py-1.5 rounded-lg">
                    <span class="w-2.5 h-2.5 rounded-full bg-primary inline-block"></span>
                    K saat ini: <strong class="text-primary"><?= $selected['k_value'] ?></strong> (<?= $kAccuracies[$selected['k_value']] ?? '—' ?>%)
                </div>
                <div class="flex items-center gap-1.5 bg-green-50 px-3 py-1.5 rounded-lg">
                    <span class="w-2.5 h-2.5 rounded-full bg-green-500 inline-block"></span>
                    K terbaik: <strong class="text-green-700"><?= $bestK ?></strong> (<?= $bestAcc ?>%)
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 shadow-sm p-5 flex items-center justify-center">
            <p class="text-sm text-outline">Kurva K tidak tersedia (data test terlalu kecil).</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- ④ Predictions detail table -->
    <?php if (!empty($evalResult['rows'])): ?>
    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-outline-variant/30 flex items-center justify-between flex-wrap gap-2">
            <h3 class="text-sm font-semibold text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-[18px]">list_alt</span>
                Detail Prediksi Data Test
                <span class="text-xs font-normal text-outline">(<?= count($evalResult['rows']) ?> data)</span>
            </h3>
            <?php
            $nCorrect   = count(array_filter($evalResult['rows'], fn($r) => $r['is_correct']));
            $nIncorrect = count($evalResult['rows']) - $nCorrect;
            ?>
            <div class="flex items-center gap-2 text-xs">
                <span class="px-2.5 py-1 rounded-full bg-green-100 text-green-800 font-semibold">✓ <?= $nCorrect ?> benar</span>
                <span class="px-2.5 py-1 rounded-full bg-red-100   text-red-800   font-semibold">✗ <?= $nIncorrect ?> salah</span>
            </div>
        </div>
        <div class="overflow-x-auto max-h-96">
            <table class="w-full text-sm text-left min-w-[680px]">
                <thead class="bg-surface-container-low sticky top-0 text-[10px] font-semibold text-outline uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Penyulang</th>
                        <th class="px-4 py-3 text-center">Bulan</th>
                        <th class="px-4 py-3 text-center">S</th>
                        <th class="px-4 py-3 text-center">O</th>
                        <th class="px-4 py-3 text-center">D</th>
                        <th class="px-4 py-3 text-center">RPN</th>
                        <th class="px-4 py-3 text-center">Aktual</th>
                        <th class="px-4 py-3 text-center">Prediksi</th>
                        <th class="px-4 py-3 text-center">Conf.</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    <?php foreach ($evalResult['rows'] as $i => $row): $ok = $row['is_correct']; ?>
                    <tr class="<?= $ok ? 'hover:bg-surface-container-low/40' : 'bg-red-50/50 hover:bg-red-50' ?> transition-colors">
                        <td class="px-4 py-2.5 text-xs text-outline"><?= $i + 1 ?></td>
                        <td class="px-4 py-2.5 font-semibold text-sm text-on-surface"><?= htmlspecialchars($row['penyulang']) ?></td>
                        <td class="px-4 py-2.5 text-center text-xs text-on-surface-variant"><?= $namaBulan[$row['bulan']] ?? $row['bulan'] ?></td>
                        <td class="px-4 py-2.5 text-center text-xs font-semibold text-on-surface"><?= $row['severity'] ?></td>
                        <td class="px-4 py-2.5 text-center text-xs font-semibold text-on-surface"><?= $row['occurrence'] ?></td>
                        <td class="px-4 py-2.5 text-center text-xs font-semibold text-on-surface"><?= $row['detection'] ?></td>
                        <td class="px-4 py-2.5 text-center text-xs font-bold text-on-surface"><?= $row['rpn'] ?></td>
                        <td class="px-4 py-2.5 text-center">
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?= $riskBdg[$row['risk_label']] ?? '' ?>"><?= $row['risk_label'] ?></span>
                        </td>
                        <td class="px-4 py-2.5 text-center">
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?= $riskBdg[$row['predicted_label']] ?? '' ?> <?= $ok ? '' : 'ring-1 ring-red-400' ?>">
                                <?= $row['predicted_label'] ?>
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-center text-xs">
                            <?php if ($ok): ?>
                            <span class="text-green-600 font-semibold">✓ <?= round($row['confidence']*100) ?>%</span>
                            <?php else: ?>
                            <span class="text-red-500 font-semibold">✗ <?= round($row['confidence']*100) ?>%</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; // evalResult ?>

</main>

<?php require APP_PATH . '/Views/partials/toast.php'; ?>
<?php require APP_PATH . '/Views/partials/sidebar_script.php'; ?>
<?php if (!empty($kAccuracies)): ?>
<script>
(function () {
    const labels  = <?= json_encode(array_keys($kAccuracies)) ?>;
    const data    = <?= json_encode(array_values($kAccuracies)) ?>;
    const activeK = <?= (int)($selected['k_value'] ?? 5) ?>;
    const maxAcc  = Math.max(...data);

    new Chart(document.getElementById('kChart'), {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Akurasi (%)',
                data,
                fill: true,
                tension: 0.35,
                borderColor: '#006492',
                backgroundColor: 'rgba(0,100,146,0.07)',
                pointBackgroundColor: labels.map(k => data[k-1] === maxAcc ? '#22c55e' : (k == activeK ? '#006492' : '#b3d4e0')),
                pointRadius:          labels.map(k => data[k-1] === maxAcc || k == activeK ? 7 : 4),
                pointBorderWidth: 2,
                pointBorderColor: labels.map(k => data[k-1] === maxAcc ? '#16a34a' : '#006492'),
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        title: ctx => 'K = ' + ctx[0].label + (ctx[0].label == activeK ? ' ★ (model ini)' : ''),
                        label: ctx => ' Akurasi: ' + ctx.parsed.y + '%',
                    }
                }
            },
            scales: {
                y: {
                    min: Math.max(0, Math.min(...data) - 10),
                    max: 100,
                    ticks: { callback: v => v + '%', font: { size: 11 } },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    title: { display: true, text: 'Nilai K', font: { size: 11 } },
                    ticks: { font: { size: 11 } },
                    grid: { color: 'rgba(0,0,0,0.04)' }
                }
            }
        }
    });
})();
</script>
<?php endif; ?>
</body>
</html>
