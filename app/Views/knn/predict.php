<?php
$flash      = Flash::get();
$baseUrl    = BASE_URL;
$userName   = htmlspecialchars($_SESSION['user_name']  ?? 'User');
$userEmail  = htmlspecialchars($_SESSION['user_email'] ?? '');
$userRole   = $_SESSION['user_role'] ?? 'viewer';

$pageHeadTitle = 'Prediksi KNN';
$activeMenu    = 'knn';
$pageTitle     = 'Prediksi KNN';
$pageIcon      = 'scatter_plot';
$backUrl       = null;
$headerActions = null;

$namaBulan = ['','Januari','Februari','Maret','April','Mei','Juni',
              'Juli','Agustus','September','Oktober','November','Desember'];

$riskBdg  = ['Rendah'=>'bg-green-100 text-green-800','Sedang'=>'bg-amber-100 text-amber-800','Tinggi'=>'bg-red-100 text-red-800'];
$riskBig  = ['Rendah'=>'bg-green-50 border-green-400 text-green-700','Sedang'=>'bg-amber-50 border-amber-400 text-amber-700','Tinggi'=>'bg-red-50 border-red-400 text-red-700'];
$riskIcon = ['Rendah'=>'check_circle','Sedang'=>'warning','Tinggi'=>'dangerous'];

$s   ??= 5; $o ??= 5; $d ??= 5; $rpn ??= 125;
$activeTab = isset($manualResult) && $manualResult ? 'manual' : (($hasBatch && $batchResult) ? 'batch' : 'manual');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php require APP_PATH . '/Views/partials/head.php'; ?>
</head>
<body class="bg-background text-on-background">

<?php require APP_PATH . '/Views/partials/sidebar.php'; ?>
<?php require APP_PATH . '/Views/partials/header.php'; ?>

<main class="ml-0 md:ml-64 mt-16 p-4 sm:p-6 min-h-screen">

    <!-- KNN Tab Navigation -->
    <div class="flex gap-1 bg-surface-container p-1 rounded-xl mb-6 w-fit">
        <?php foreach ([
            ['train',    'Training',      'model_training'],
            ['evaluate', 'Evaluasi Model','analytics'],
            ['predict',  'Prediksi',      'scatter_plot'],
        ] as [$key, $lbl, $ico]): $active = $key === 'predict'; ?>
        <a href="<?= $baseUrl ?>/knn/<?= $key ?>?tahun=<?= $tahun ?><?= $selected && $key==='predict' ? '&model_id='.$selected['id'] : '' ?>"
           class="flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold transition-colors
                  <?= $active ? 'bg-white text-primary shadow-sm' : 'text-on-surface-variant hover:text-on-surface' ?>">
            <span class="material-symbols-outlined text-[16px]"><?= $ico ?></span>
            <?= $lbl ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Model selector -->
    <div class="flex flex-wrap items-center gap-3 mb-6 bg-surface-container-lowest p-4 rounded-xl border border-outline-variant/30 shadow-sm">
        <label class="text-xs text-outline font-semibold">Model:</label>
        <form method="GET" action="<?= $baseUrl ?>/knn/predict" class="flex flex-wrap items-center gap-3 flex-1">
            <select name="model_id" onchange="this.form.submit()"
                    class="text-sm border border-outline-variant rounded-lg pl-3 pr-10 py-1.5 bg-white text-on-surface focus:ring-1 focus:ring-primary outline-none flex-1 min-w-[260px]">
                <option value="">-- Pilih Model --</option>
                <?php foreach ($history as $m):
                    $acc = $m['accuracy'] !== null ? ' · Akurasi: '.round($m['accuracy']*100,1).'%' : '';
                ?>
                <option value="<?= $m['id'] ?>" <?= ($selected && $selected['id']==$m['id']) ? 'selected' : '' ?>>
                    Model #<?= $m['id'] ?> — K=<?= $m['k_value'] ?>, <?= ucfirst($m['distance_metric']) ?>, <?= date('d M Y', strtotime($m['trained_at'])) ?><?= $acc ?>
                </option>
                <?php endforeach; ?>
            </select>
            <select name="tahun" onchange="this.form.submit()"
                    class="text-sm border border-outline-variant rounded-lg pl-3 pr-8 py-1.5 bg-white text-on-surface focus:ring-1 focus:ring-primary outline-none w-28">
                <?php foreach ($years as $y): ?>
                <option value="<?= $y ?>" <?= $y==$tahun?'selected':'' ?>><?= $y ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if (!$selected): ?>
    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 p-12 text-center">
        <span class="material-symbols-outlined text-[56px] text-outline/30 block mb-3">model_training</span>
        <p class="text-on-surface font-semibold mb-1">Belum ada model terlatih</p>
        <p class="text-sm text-outline mb-4">Latih model KNN terlebih dahulu di tab Training.</p>
        <a href="<?= $baseUrl ?>/knn/train?tahun=<?= $tahun ?>"
           class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-xl text-sm font-semibold hover:bg-on-primary-fixed-variant transition-colors">
            <span class="material-symbols-outlined text-[16px]">model_training</span> Latih Model
        </a>
    </div>

    <?php else: ?>

    <!-- Prediction mode tabs -->
    <div class="border-b border-outline-variant/30 mb-6 flex gap-1">
        <button onclick="switchTab('manual')" id="tab-manual"
                class="tab-btn px-5 py-2.5 text-sm font-semibold border-b-2 transition-colors
                       <?= $activeTab==='manual' ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface' ?>">
            <span class="material-symbols-outlined text-[16px] align-middle mr-1">input</span>
            Prediksi Manual
        </button>
        <button onclick="switchTab('batch')" id="tab-batch"
                class="tab-btn px-5 py-2.5 text-sm font-semibold border-b-2 transition-colors
                       <?= $activeTab==='batch' ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface' ?>">
            <span class="material-symbols-outlined text-[16px] align-middle mr-1">table_rows</span>
            Prediksi Batch
            <?php if ($hasBatch && $batchResult): ?>
            <span class="ml-1 px-1.5 py-0.5 rounded-full bg-primary text-white text-[10px] font-bold"><?= count($batchResult) ?></span>
            <?php endif; ?>
        </button>
    </div>

    <!-- MANUAL TAB -->
    <div id="pane-manual" class="<?= $activeTab==='manual' ? '' : 'hidden' ?>">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Manual form -->
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 shadow-sm p-5">
                <h3 class="text-sm font-semibold text-on-surface mb-5 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-[20px]">tune</span>
                    Input Nilai FMEA
                </h3>
                <form method="POST" action="<?= $baseUrl ?>/knn/predict" id="manualForm">
                    <input type="hidden" name="model_id" value="<?= $selected['id'] ?>">
                    <input type="hidden" name="tahun"    value="<?= $tahun ?>">

                    <?php foreach ([
                        ['severity',   'Severity (S)',   'Dampak keparahan kegagalan', $s],
                        ['occurrence', 'Occurrence (O)', 'Frekuensi kegagalan terjadi', $o],
                        ['detection',  'Detection (D)',  'Kemampuan mendeteksi kegagalan', $d],
                    ] as [$name, $lbl, $desc, $val]): ?>
                    <div class="mb-5">
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-semibold text-on-surface-variant"><?= $lbl ?></label>
                            <span class="px-2.5 py-0.5 rounded-lg bg-primary text-white text-sm font-bold" id="<?= $name ?>Val"><?= $val ?></span>
                        </div>
                        <input type="range" name="<?= $name ?>" id="<?= $name ?>Slider"
                               min="1" max="10" value="<?= $val ?>"
                               class="w-full accent-primary cursor-pointer"
                               oninput="updateSlider('<?= $name ?>', this.value)">
                        <div class="flex justify-between text-[10px] text-outline mt-0.5">
                            <span>1</span><span><?= $desc ?></span><span>10</span>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div class="bg-surface-container rounded-xl p-4 mb-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-[10px] text-outline font-semibold uppercase tracking-wider">RPN (S × O × D)</p>
                                <p class="text-3xl font-bold text-primary mt-0.5" id="rpnDisplay"><?= $rpn ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] text-outline font-semibold uppercase tracking-wider">Estimasi Risk</p>
                                <p class="text-sm font-bold mt-0.5" id="rpnLabel">
                                    <?php $rpnLabel = $rpn<=9?'Rendah':($rpn<=99?'Sedang':'Tinggi'); ?>
                                    <span class="px-2.5 py-1 rounded-full <?= $riskBdg[$rpnLabel] ?>"><?= $rpnLabel ?></span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <button type="submit"
                            class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-primary text-white rounded-xl text-sm font-semibold hover:bg-on-primary-fixed-variant transition-colors shadow-sm">
                        <span class="material-symbols-outlined text-[18px]">scatter_plot</span>
                        Prediksi dengan KNN
                    </button>
                </form>
            </div>

            <!-- Manual result -->
            <div class="space-y-4">
                <?php if (!empty($manualResult) && $manualResult['predicted_label']): ?>
                <?php
                $pred   = $manualResult['predicted_label'];
                $conf   = round($manualResult['confidence'] * 100);
                $bigCls = $riskBig[$pred]  ?? 'bg-surface-container border-outline text-on-surface';
                $ico    = $riskIcon[$pred] ?? 'help';
                $inp    = $manualResult['input'] ?? [];
                ?>
                <!-- Result Card -->
                <div class="border-2 rounded-xl p-5 <?= $bigCls ?>">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider opacity-70 mb-1">Hasil Prediksi KNN</p>
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-outlined text-[40px]" style="font-variation-settings:'FILL' 1;"><?= $ico ?></span>
                                <div>
                                    <p class="text-2xl font-bold"><?= $pred ?></p>
                                    <p class="text-sm opacity-80">Kepercayaan: <?= $conf ?>%</p>
                                </div>
                            </div>
                        </div>
                        <div class="text-right text-xs opacity-70">
                            <p>S=<?= $inp['s']??'-' ?> · O=<?= $inp['o']??'-' ?> · D=<?= $inp['d']??'-' ?></p>
                            <p class="font-bold mt-0.5">RPN = <?= $inp['rpn']??'-' ?></p>
                        </div>
                    </div>

                    <!-- Vote breakdown -->
                    <?php if (!empty($manualResult['votes'])): ?>
                    <div class="mt-4 pt-4 border-t border-current/20">
                        <p class="text-xs font-semibold opacity-70 mb-2">Distribusi Suara (K=<?= $selected['k_value'] ?>):</p>
                        <div class="flex items-center gap-2 flex-wrap">
                            <?php foreach ($manualResult['votes'] as $lbl => $cnt): $bdg = $riskBdg[$lbl] ?? ''; ?>
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= $bdg ?>"><?= $lbl ?>: <?= $cnt ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Nearest Neighbors -->
                <?php if (!empty($manualResult['neighbors'])): ?>
                <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b border-outline-variant/30">
                        <p class="text-sm font-semibold text-on-surface"><?= count($manualResult['neighbors']) ?> Tetangga Terdekat</p>
                    </div>
                    <table class="w-full text-xs">
                        <thead class="bg-surface-container-low text-[10px] font-semibold text-outline uppercase tracking-wider">
                            <tr>
                                <th class="px-3 py-2 text-left">Penyulang</th>
                                <th class="px-3 py-2 text-center">S</th>
                                <th class="px-3 py-2 text-center">O</th>
                                <th class="px-3 py-2 text-center">D</th>
                                <th class="px-3 py-2 text-center">RPN</th>
                                <th class="px-3 py-2 text-center">Label</th>
                                <th class="px-3 py-2 text-right">Jarak</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/20">
                            <?php foreach ($manualResult['neighbors'] as $nb): $bdg = $riskBdg[$nb['risk_label']] ?? ''; ?>
                            <tr class="hover:bg-surface-container-low/50">
                                <td class="px-3 py-2 font-semibold text-on-surface"><?= htmlspecialchars($nb['penyulang']) ?></td>
                                <td class="px-3 py-2 text-center"><?= $nb['severity'] ?></td>
                                <td class="px-3 py-2 text-center"><?= $nb['occurrence'] ?></td>
                                <td class="px-3 py-2 text-center"><?= $nb['detection'] ?></td>
                                <td class="px-3 py-2 text-center font-semibold"><?= $nb['rpn'] ?></td>
                                <td class="px-3 py-2 text-center">
                                    <span class="px-2 py-0.5 rounded-full font-semibold <?= $bdg ?>"><?= $nb['risk_label'] ?></span>
                                </td>
                                <td class="px-3 py-2 text-right text-outline"><?= $nb['distance'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <?php else: ?>
                <!-- Empty result state -->
                <div class="bg-surface-container-lowest rounded-xl border border-dashed border-outline-variant p-10 text-center">
                    <span class="material-symbols-outlined text-[48px] text-outline/30 block mb-3">scatter_plot</span>
                    <p class="text-sm font-semibold text-on-surface mb-1">Belum ada prediksi</p>
                    <p class="text-xs text-outline">Atur nilai S, O, D di kiri dan klik "Prediksi dengan KNN".</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- BATCH TAB -->
    <div id="pane-batch" class="<?= $activeTab==='batch' ? '' : 'hidden' ?>">
        <!-- Batch action bar -->
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 shadow-sm px-5 py-4 mb-5 flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-on-surface">Prediksi Semua Data Berlabel</p>
                <p class="text-xs text-outline mt-0.5">Jalankan KNN pada seluruh data berlabel tahun <?= $tahun ?> menggunakan model terpilih.</p>
            </div>
            <div class="flex items-center gap-3">
                <?php if ($hasBatch): ?>
                <form method="POST" action="<?= $baseUrl ?>/knn/predict/clear"
                      data-confirm="Hapus seluruh hasil prediksi batch untuk model ini? Tindakan tidak dapat dibatalkan."
                      data-confirm-title="Hapus Semua Prediksi" data-confirm-type="danger" data-confirm-ok="Hapus Semua">
                    <input type="hidden" name="model_id" value="<?= $selected['id'] ?>">
                    <input type="hidden" name="tahun"    value="<?= $tahun ?>">
                    <button type="submit"
                            class="flex items-center gap-2 px-4 py-2.5 bg-error text-white rounded-xl text-sm font-semibold hover:bg-red-700 transition-colors shadow-sm">
                        <span class="material-symbols-outlined text-[18px]">delete_sweep</span>
                        Hapus Semua Prediksi
                    </button>
                </form>
                <?php endif; ?>
                <form method="POST" action="<?= $baseUrl ?>/knn/predict/batch"
                      data-confirm="Jalankan prediksi KNN pada seluruh data berlabel tahun <?= $tahun ?>. Hasil prediksi batch sebelumnya akan digantikan."
                      data-confirm-title="Prediksi Batch" data-confirm-type="warning" data-confirm-ok="Jalankan">
                    <input type="hidden" name="model_id" value="<?= $selected['id'] ?>">
                    <input type="hidden" name="tahun"    value="<?= $tahun ?>">
                    <button type="submit"
                            class="flex items-center gap-2 px-4 py-2.5 bg-primary text-white rounded-xl text-sm font-semibold hover:bg-on-primary-fixed-variant transition-colors shadow-sm">
                        <span class="material-symbols-outlined text-[18px]">play_arrow</span>
                        <?= $hasBatch ? 'Jalankan Ulang Prediksi' : 'Jalankan Prediksi Batch' ?>
                    </button>
                </form>
            </div>
        </div>

        <?php if ($hasBatch && !empty($batchResult)): ?>
        <?php
        // Pisahkan train vs test vs unassigned
        $trainRows = array_filter($batchResult, fn($r) => ($r['split_type'] ?? '') === 'train');
        $testRows  = array_filter($batchResult, fn($r) => ($r['split_type'] ?? '') === 'test');
        $otherRows = array_filter($batchResult, fn($r) => !in_array($r['split_type'] ?? '', ['train','test']));

        $stats = fn(array $rows) => [
            'total'   => count($rows),
            'correct' => count(array_filter($rows, fn($r) => $r['predicted_label'] === $r['actual_label'])),
        ];
        $sTrain = $stats($trainRows);
        $sTest  = $stats($testRows);
        $sAll   = $stats($batchResult);
        $accFn  = fn($s) => $s['total'] > 0 ? round($s['correct']/$s['total']*100,1).'%' : '—';
        ?>

        <!-- Penjelasan perbedaan akurasi -->
        <div class="bg-primary/5 border border-primary/20 rounded-xl p-4 mb-5 flex items-start gap-3">
            <span class="material-symbols-outlined text-primary text-[20px] mt-0.5 flex-shrink-0">info</span>
            <div class="text-xs text-on-surface-variant leading-relaxed">
                <strong class="text-on-surface">Kenapa akurasi batch berbeda dengan evaluasi model?</strong><br>
                Halaman <em>Evaluasi Model</em> hanya mengukur akurasi pada <strong>data test</strong> (data baru yang belum pernah dilihat model) — inilah akurasi yang sesungguhnya.
                Prediksi batch mencakup <strong>data train</strong> yang sudah "dihafal" KNN, sehingga akurasinya terlihat lebih tinggi.
                <strong>Gunakan akurasi data test sebagai acuan utama.</strong>
            </div>
        </div>

        <!-- Statistik dipisah train / test -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-5">
            <!-- Test set -->
            <div class="bg-surface-container-lowest rounded-xl border-2 border-primary shadow-sm p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2 py-0.5 rounded-full bg-secondary/10 text-secondary text-[10px] font-bold uppercase">Test Set</span>
                    <span class="text-[10px] text-outline">← Acuan utama</span>
                </div>
                <p class="text-2xl font-bold text-primary"><?= $accFn($sTest) ?></p>
                <p class="text-xs text-outline mt-0.5"><?= $sTest['correct'] ?> benar dari <?= $sTest['total'] ?> data</p>
            </div>
            <!-- Train set -->
            <div class="bg-surface-container-lowest rounded-xl border border-amber-300 shadow-sm p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[10px] font-bold uppercase">Train Set</span>
                    <span class="text-[10px] text-outline">Data latih</span>
                </div>
                <p class="text-2xl font-bold text-amber-700"><?= $accFn($sTrain) ?></p>
                <p class="text-xs text-outline mt-0.5"><?= $sTrain['correct'] ?> benar dari <?= $sTrain['total'] ?> data</p>
            </div>
            <!-- All -->
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 shadow-sm p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2 py-0.5 rounded-full bg-surface-container text-on-surface-variant text-[10px] font-bold uppercase">Semua Data</span>
                </div>
                <p class="text-2xl font-bold text-on-surface"><?= $accFn($sAll) ?></p>
                <p class="text-xs text-outline mt-0.5"><?= $sAll['correct'] ?> benar dari <?= $sAll['total'] ?> data</p>
            </div>
        </div>

        <!-- Batch results table -->
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 shadow-sm overflow-hidden">
            <div class="overflow-x-auto max-h-[520px]">
                <table class="w-full text-sm text-left min-w-[680px]">
                    <thead class="bg-surface-container-low sticky top-0 text-[10px] font-semibold text-outline uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">Penyulang</th>
                            <th class="px-4 py-3 text-center">Bulan</th>
                            <th class="px-4 py-3 text-center">Split</th>
                            <th class="px-4 py-3 text-center">Label Aktual</th>
                            <th class="px-4 py-3 text-center">Prediksi KNN</th>
                            <th class="px-4 py-3 text-center">Conf.</th>
                            <th class="px-4 py-3 text-center">Hasil</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/20">
                        <?php foreach ($batchResult as $i => $row):
                            $ok       = $row['predicted_label'] === $row['actual_label'];
                            $splitTyp = $row['split_type'] ?? null;
                            $rowBg    = $ok ? '' : 'bg-red-50/40';
                        ?>
                        <tr class="<?= $rowBg ?> hover:bg-surface-container-low/50 transition-colors">
                            <td class="px-4 py-2.5 text-xs text-outline"><?= $i + 1 ?></td>
                            <td class="px-4 py-2.5 font-semibold text-sm text-on-surface"><?= htmlspecialchars($row['penyulang']) ?></td>
                            <td class="px-4 py-2.5 text-center text-xs text-on-surface-variant"><?= $namaBulan[$row['bulan']] ?? $row['bulan'] ?></td>
                            <td class="px-4 py-2.5 text-center">
                                <?php if ($splitTyp === 'test'): ?>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-secondary/10 text-secondary">Test</span>
                                <?php elseif ($splitTyp === 'train'): ?>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700">Train</span>
                                <?php else: ?>
                                <span class="text-outline text-xs">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                <?php if ($row['actual_label']): ?>
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?= $riskBdg[$row['actual_label']] ?? '' ?>"><?= $row['actual_label'] ?></span>
                                <?php else: ?><span class="text-outline text-xs">—</span><?php endif; ?>
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?= $riskBdg[$row['predicted_label']] ?? '' ?> <?= $ok ? '' : 'ring-1 ring-red-400' ?>">
                                    <?= $row['predicted_label'] ?>
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-center text-xs text-outline"><?= round($row['confidence'] * 100) ?>%</td>
                            <td class="px-4 py-2.5 text-center">
                                <?= $ok
                                    ? '<span class="text-green-600 font-bold text-lg">✓</span>'
                                    : '<span class="text-red-500 font-bold text-lg">✗</span>'
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php else: ?>
        <div class="bg-surface-container-lowest rounded-xl border border-dashed border-outline-variant p-10 text-center">
            <span class="material-symbols-outlined text-[48px] text-outline/30 block mb-3">table_rows</span>
            <p class="text-sm font-semibold text-on-surface mb-1">Belum ada hasil prediksi batch</p>
            <p class="text-xs text-outline">Klik "Jalankan Prediksi Batch" untuk memprediksi seluruh data berlabel.</p>
        </div>
        <?php endif; ?>
    </div>

    <?php endif; // selected ?>

</main>

<?php require APP_PATH . '/Views/partials/toast.php'; ?>
<?php require APP_PATH . '/Views/partials/sidebar_script.php'; ?>
<script>
// Slider updates
function updateSlider(name, val) {
    document.getElementById(name + 'Val').textContent = val;
    const s   = parseInt(document.getElementById('severitySlider').value);
    const o   = parseInt(document.getElementById('occurrenceSlider').value);
    const d   = parseInt(document.getElementById('detectionSlider').value);
    const rpn = s * o * d;
    document.getElementById('rpnDisplay').textContent = rpn;
    const lbl = rpn <= 9 ? 'Rendah' : (rpn <= 99 ? 'Sedang' : 'Tinggi');
    const cls = { Rendah: 'bg-green-100 text-green-800', Sedang: 'bg-amber-100 text-amber-800', Tinggi: 'bg-red-100 text-red-800' };
    const el  = document.getElementById('rpnLabel');
    el.innerHTML = `<span class="px-2.5 py-1 rounded-full ${cls[lbl]}">${lbl}</span>`;
}

// Tab switcher
function switchTab(name) {
    ['manual','batch'].forEach(t => {
        document.getElementById('pane-' + t).classList.toggle('hidden', t !== name);
        const btn = document.getElementById('tab-' + t);
        btn.classList.toggle('border-primary',   t === name);
        btn.classList.toggle('text-primary',      t === name);
        btn.classList.toggle('border-transparent',t !== name);
        btn.classList.toggle('text-on-surface-variant', t !== name);
    });
}
</script>
</body>
</html>
