<?php
$flash      = Flash::get();
$baseUrl    = BASE_URL;
$userName   = htmlspecialchars($_SESSION['user_name']  ?? 'User');
$userEmail  = htmlspecialchars($_SESSION['user_email'] ?? '');
$userRole   = $_SESSION['user_role'] ?? 'viewer';

$pageHeadTitle = 'Training KNN';
$activeMenu    = 'knn';
$pageTitle     = 'Prediksi KNN';
$pageIcon      = 'model_training';
$backUrl       = null;
$headerActions = null;

$namaBulan = ['','Januari','Februari','Maret','April','Mei','Juni',
              'Juli','Agustus','September','Oktober','November','Desember'];

$riskCls = [
    'Rendah' => 'bg-green-100 text-green-800',
    'Sedang' => 'bg-amber-100 text-amber-800',
    'Tinggi' => 'bg-red-100   text-red-800',
];

function knnQ(int $tahun, string $extra = ''): string {
    return '?tahun=' . $tahun . ($extra ? '&' . $extra : '');
}
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
        ] as [$key, $lbl, $ico]): $active = $key === 'train'; ?>
        <a href="<?= $baseUrl ?>/knn/<?= $key ?><?= knnQ($tahun) ?>"
           class="flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold transition-colors
                  <?= $active ? 'bg-white text-primary shadow-sm' : 'text-on-surface-variant hover:text-on-surface' ?>">
            <span class="material-symbols-outlined text-[16px]"><?= $ico ?></span>
            <?= $lbl ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Year selector -->
    <form method="GET" action="<?= $baseUrl ?>/knn/train" class="flex items-center gap-3 mb-6">
        <label class="text-xs text-outline font-semibold">Tahun:</label>
        <select name="tahun" onchange="this.form.submit()"
                class="text-sm border border-outline-variant rounded-lg pl-3 pr-8 py-1.5 bg-white text-on-surface focus:ring-1 focus:ring-primary outline-none w-28">
            <?php foreach ($years as $y): ?>
            <option value="<?= $y ?>" <?= $y == $tahun ? 'selected' : '' ?>><?= $y ?></option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if (!$hasSplit): ?>
    <!-- No split warning -->
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 mb-6 flex items-start gap-3">
        <span class="material-symbols-outlined text-amber-600 text-[24px] flex-shrink-0 mt-0.5">warning</span>
        <div>
            <p class="text-sm font-semibold text-amber-800">Belum ada split data untuk tahun <?= $tahun ?></p>
            <p class="text-xs text-amber-700 mt-0.5 mb-3">Split data train/test diperlukan sebelum melatih model KNN. Pergi ke halaman Labeling FMEA untuk melakukan split.</p>
            <a href="<?= $baseUrl ?>/labeling/split?tahun=<?= $tahun ?>"
               class="inline-flex items-center gap-1.5 px-3 py-2 bg-amber-600 text-white rounded-lg text-xs font-semibold hover:bg-amber-700 transition-colors">
                <span class="material-symbols-outlined text-[14px]">call_split</span>
                Split Data Sekarang
            </a>
        </div>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left: Training Config Form -->
        <div class="lg:col-span-1">
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-on-surface mb-5 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-[20px]">tune</span>
                    Konfigurasi Model
                </h2>

                <form method="POST" action="<?= $baseUrl ?>/knn/train" id="trainForm">
                    <input type="hidden" name="tahun" value="<?= $tahun ?>">

                    <!-- K Value -->
                    <div class="mb-5">
                        <label class="block text-xs font-semibold text-on-surface-variant mb-2">
                            Nilai K (Jumlah Tetangga)
                        </label>
                        <div class="flex items-center gap-3">
                            <input type="range" name="k_value" id="kSlider" min="1" max="20" value="3"
                                   class="flex-1 accent-primary cursor-pointer"
                                   oninput="document.getElementById('kVal').textContent=this.value">
                            <span class="w-10 h-10 flex items-center justify-center bg-primary text-white rounded-xl font-bold text-lg" id="kVal">3</span>
                        </div>
                        <p class="text-[11px] text-outline mt-1">K ganjil disarankan untuk menghindari tie (1–20). Data train tersedia: <strong><?= $trainCount ?></strong></p>
                    </div>

                    <!-- Distance Metric -->
                    <div class="mb-5">
                        <label class="block text-xs font-semibold text-on-surface-variant mb-2">Metrik Jarak</label>
                        <div class="grid grid-cols-2 gap-2">
                            <?php foreach ([['euclidean','Euclidean','Standar, sensitif terhadap outlier'],['manhattan','Manhattan','Lebih robust terhadap noise']] as [$val,$lbl,$desc]): ?>
                            <label class="metric-btn flex flex-col border rounded-lg p-3 cursor-pointer transition-all
                                          <?= $val==='euclidean' ? 'border-primary bg-primary/5' : 'border-outline-variant hover:border-primary/50' ?>"
                                   data-val="<?= $val ?>">
                                <input type="radio" name="distance_metric" value="<?= $val ?>"
                                       <?= $val==='euclidean'?'checked':'' ?> class="hidden">
                                <span class="text-sm font-semibold text-on-surface"><?= $lbl ?></span>
                                <span class="text-[10px] text-outline mt-0.5"><?= $desc ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Features -->
                    <div class="mb-6">
                        <label class="block text-xs font-semibold text-on-surface-variant mb-2">Fitur yang Digunakan</label>
                        <div class="space-y-2">
                            <?php foreach ([
                                ['tier1_inpeksi',             'Tier 1 - Inspeksi',             'Jumlah inspeksi Tier 1', true, false],
                                ['tier1_temuan',              'Tier 1 - Temuan',               'Jumlah temuan Tier 1', true, false],
                                ['tier2_inpeksi',             'Tier 2 - Inspeksi',             'Jumlah inspeksi Tier 2', true, false],
                                ['tier2_temuan',              'Tier 2 - Temuan',               'Jumlah temuan Tier 2', true, false],
                                ['pengukuran',                'Pengukuran',                    'Jumlah aktivitas pengukuran', true, false],
                                ['pergantian_fco',            'Pergantian FCO',                'Jumlah pergantian Fuse Cut Out', true, false],
                                ['penyeimbangan_beban_gardu', 'Penyeimbangan Beban Gardu',     'Jumlah penyeimbangan beban gardu', true, false],
                                ['perbaikan_grounding_trafo', 'Perbaikan Grounding Trafo',     'Jumlah perbaikan grounding trafo', true, false],
                                ['penghalang_panjat',         'Penghalang Panjat',             'Jumlah pemasangan penghalang panjat', true, false],
                            ] as [$val, $lbl, $desc, $checked, $required]): ?>
                            <label class="flex items-start gap-2.5 p-2.5 rounded-lg hover:bg-surface-container cursor-pointer group transition-colors">
                                <input type="checkbox" name="features[]" value="<?= $val ?>"
                                       <?= $checked ? 'checked' : '' ?>
                                       <?= $required ? 'data-required="true"' : '' ?>
                                       class="mt-0.5 rounded border-outline-variant text-primary focus:ring-primary cursor-pointer">
                                <div>
                                    <p class="text-sm font-semibold text-on-surface"><?= $lbl ?></p>
                                    <p class="text-[10px] text-outline"><?= $desc ?></p>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <button type="button" onclick="confirmTrain()"
                            class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-primary text-white rounded-xl text-sm font-semibold hover:bg-on-primary-fixed-variant transition-colors shadow-sm
                                   <?= !$hasSplit ? 'opacity-50 cursor-not-allowed' : '' ?>"
                            <?= !$hasSplit ? 'disabled' : '' ?>>
                        <span class="material-symbols-outlined text-[18px]">model_training</span>
                        Latih Model KNN
                    </button>
                </form>
            </div>
        </div>

        <!-- Right: Stats + History -->
        <div class="lg:col-span-2 space-y-5">

            <!-- Split Stats -->
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-on-surface mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-[20px]">dataset</span>
                    Dataset Split — Tahun <?= $tahun ?>
                </h2>

                <?php if (empty($splitStats)): ?>
                <p class="text-sm text-outline">Belum ada data berlabel untuk tahun ini.</p>
                <?php else: ?>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
                    <?php
                    $totalLabeled = array_sum(array_column($splitStats, 'total'));
                    foreach ([
                        ['Total Berlabel', $totalLabeled,  'border-primary',   'text-primary'],
                        ['Data Train',     $trainCount,    'border-on-surface','text-on-surface'],
                        ['Data Test',      $testCount,     'border-secondary', 'text-secondary'],
                        ['Unassigned',     $totalLabeled - $trainCount - $testCount, 'border-outline', 'text-outline'],
                    ] as [$lbl, $val, $border, $cls]): ?>
                    <div class="p-3 bg-surface-container rounded-xl border-l-4 <?= $border ?>">
                        <p class="text-[10px] text-outline font-semibold uppercase tracking-wider"><?= $lbl ?></p>
                        <p class="text-xl font-bold <?= $cls ?>"><?= $val ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php foreach ($splitStats as $s):
                    $tot = (int)$s['total']; $tr = (int)$s['train']; $te = (int)$s['test'];
                    $rl  = $s['risk_label'];
                    $barCls = $rl==='Rendah' ? 'bg-green-500' : ($rl==='Sedang' ? 'bg-amber-500' : 'bg-red-500');
                    $bdg    = $rl==='Rendah' ? 'bg-green-100 text-green-800' : ($rl==='Sedang' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800');
                ?>
                <div class="flex items-center gap-3 py-2 border-b border-outline-variant/20 last:border-0">
                    <span class="w-20 text-xs px-2 py-0.5 rounded-full font-semibold text-center <?= $bdg ?>"><?= $rl ?></span>
                    <div class="flex-1 flex rounded-full overflow-hidden h-2.5 bg-outline/10">
                        <?php if ($tr > 0): ?><div class="<?= $barCls ?> h-full" style="width:<?= $tot>0?round($tr/$tot*100):0 ?>%"></div><?php endif; ?>
                        <?php if ($te > 0): ?><div class="<?= $barCls ?> opacity-40 h-full" style="width:<?= $tot>0?round($te/$tot*100):0 ?>%"></div><?php endif; ?>
                    </div>
                    <span class="text-xs text-outline w-32 text-right">Train: <?= $tr ?> · Test: <?= $te ?></span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Model History -->
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-outline-variant/30 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[20px]">history</span>
                        Riwayat Model (<?= count($history) ?> model)
                    </h2>
                </div>
                <?php if (empty($history)): ?>
                <div class="px-5 py-8 text-center">
                    <span class="material-symbols-outlined text-[40px] text-outline/30 block mb-2">model_training</span>
                    <p class="text-sm text-outline">Belum ada model yang dilatih untuk tahun <?= $tahun ?>.</p>
                </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left min-w-[600px]">
                        <thead class="bg-surface-container-low text-[11px] font-semibold text-outline uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3">Dilatih</th>
                                <th class="px-4 py-3 text-center">K</th>
                                <th class="px-4 py-3 text-center">Metrik</th>
                                <th class="px-4 py-3 text-center">Train</th>
                                <th class="px-4 py-3 text-center">Test</th>
                                <th class="px-4 py-3 text-center">Akurasi</th>
                                <th class="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/20">
                            <?php foreach ($history as $m): ?>
                            <tr class="hover:bg-surface-container-low/50 transition-colors group">
                                <td class="px-4 py-3 text-xs text-on-surface-variant">
                                    <?= date('d M Y H:i', strtotime($m['trained_at'])) ?>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-0.5 rounded-md bg-primary/10 text-primary text-xs font-bold"><?= $m['k_value'] ?></span>
                                </td>
                                <td class="px-4 py-3 text-center text-xs text-on-surface-variant capitalize"><?= $m['distance_metric'] ?></td>
                                <td class="px-4 py-3 text-center text-xs font-semibold text-on-surface"><?= $m['train_count'] ?></td>
                                <td class="px-4 py-3 text-center text-xs font-semibold text-on-surface"><?= $m['test_count'] ?></td>
                                <td class="px-4 py-3 text-center">
                                    <?php if ($m['accuracy'] !== null): ?>
                                    <?php $acc = round($m['accuracy']*100,1); $accCls = $acc>=80?'text-green-700 bg-green-50':($acc>=60?'text-amber-700 bg-amber-50':'text-red-700 bg-red-50'); ?>
                                    <span class="px-2 py-0.5 rounded-md text-xs font-bold <?= $accCls ?>"><?= $acc ?>%</span>
                                    <?php else: ?><span class="text-outline text-xs">—</span><?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <a href="<?= $baseUrl ?>/knn/evaluate?model_id=<?= $m['id'] ?>&tahun=<?= $tahun ?>"
                                           class="p-1.5 rounded-lg text-primary hover:bg-primary-fixed transition-colors" title="Evaluasi">
                                            <span class="material-symbols-outlined text-[16px]">analytics</span>
                                        </a>
                                        <a href="<?= $baseUrl ?>/knn/predict?model_id=<?= $m['id'] ?>&tahun=<?= $tahun ?>"
                                           class="p-1.5 rounded-lg text-secondary hover:bg-secondary-container transition-colors" title="Prediksi">
                                            <span class="material-symbols-outlined text-[16px]">scatter_plot</span>
                                        </a>
                                        <form method="POST" action="<?= $baseUrl ?>/knn/delete/<?= $m['id'] ?>"
                                              data-confirm="Hapus model KNN ini? File model akan dihapus permanen."
                                              data-confirm-title="Hapus Model KNN" data-confirm-type="danger" data-confirm-ok="Hapus">
                                            <input type="hidden" name="tahun" value="<?= $tahun ?>">
                                            <button class="p-1.5 rounded-lg text-error hover:bg-error-container transition-colors" title="Hapus">
                                                <span class="material-symbols-outlined text-[16px]">delete</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

</main>

<?php require APP_PATH . '/Views/partials/toast.php'; ?>
<?php require APP_PATH . '/Views/partials/sidebar_script.php'; ?>
<script>
document.querySelectorAll('.metric-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.metric-btn').forEach(b => {
            b.classList.remove('border-primary','bg-primary/5');
            b.classList.add('border-outline-variant');
        });
        btn.classList.add('border-primary','bg-primary/5');
        btn.classList.remove('border-outline-variant');
        btn.querySelector('input[type=radio]').checked = true;
    });
});

function confirmTrain() {
    const k      = document.getElementById('kSlider').value;
    const metric = document.querySelector('input[name=distance_metric]:checked')?.value ?? 'euclidean';
    const feats  = [...document.querySelectorAll('input[name="features[]"]:checked')].map(c => c.value);
    if (feats.length === 0) {
        showAlert('Pilih minimal satu fitur sebelum melatih model.', 'Fitur Belum Dipilih');
        return;
    }
    showDialog({
        title:       'Latih Model KNN',
        message:     `Konfigurasi training:\n• K = ${k}\n• Metrik: ${metric}\n• Fitur: ${feats.join(', ')}`,
        type:        'info',
        confirmText: 'Mulai Training',
        onConfirm:   () => document.getElementById('trainForm').submit(),
    });
}
</script>
</body>
</html>
