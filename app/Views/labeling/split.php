<?php
$flash      = Flash::get();
$baseUrl    = BASE_URL;
$userName   = htmlspecialchars($_SESSION['user_name']  ?? 'User');
$userEmail  = htmlspecialchars($_SESSION['user_email'] ?? '');
$userRole   = $_SESSION['user_role'] ?? 'viewer';

$pageHeadTitle = 'Split Data Labeling';
$activeMenu    = 'labeling';
$pageTitle     = 'Split Data Labeling';
$pageIcon      = 'call_split';
$backUrl       = $baseUrl . '/labeling';
$headerActions = null;

$labeled   = (int)($summary['labeled'] ?? 0);
$total_all = (int)($summary['total']   ?? 0);

// Aggregate split totals from stats
$grandTrain      = 0;
$grandTest       = 0;
$grandUnassigned = 0;
foreach ($stats as $s) {
    $grandTrain      += (int)$s['train'];
    $grandTest       += (int)$s['test'];
    $grandUnassigned += (int)$s['unassigned'];
}

$riskCls = [
    'Rendah' => ['bar'=>'bg-green-500','badge'=>'bg-green-100 text-green-800'],
    'Sedang' => ['bar'=>'bg-amber-500','badge'=>'bg-amber-100 text-amber-800'],
    'Tinggi' => ['bar'=>'bg-red-500',  'badge'=>'bg-red-100   text-red-800'],
];
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

    <!-- Year filter -->
    <form method="GET" action="<?= $baseUrl ?>/labeling/split"
          class="flex items-center gap-3 mb-6 bg-surface-container-lowest p-4 rounded-xl shadow-sm border border-outline-variant/30 w-fit">
        <label class="text-xs text-outline font-semibold">Tahun:</label>
        <select name="tahun" onchange="this.form.submit()"
                class="text-sm border border-outline-variant rounded-lg pl-3 pr-8 py-1.5 bg-white text-on-surface focus:ring-1 focus:ring-primary outline-none w-28">
            <?php foreach ($years as $y): ?>
            <option value="<?= $y ?>" <?= $y == $tahun ? 'selected' : '' ?>><?= $y ?></option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ($labeled === 0): ?>
    <!-- Empty state -->
    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 shadow-sm p-12 text-center">
        <span class="material-symbols-outlined text-[56px] text-outline/40 block mb-3">label_off</span>
        <p class="text-on-surface font-semibold mb-1">Belum ada data berlabel</p>
        <p class="text-sm text-outline mb-4">Lakukan labeling FMEA terlebih dahulu sebelum melakukan split data.</p>
        <a href="<?= $baseUrl ?>/labeling" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-xl text-sm font-semibold hover:bg-on-primary-fixed-variant transition-colors">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span> Ke Halaman Labeling
        </a>
    </div>

    <?php else: ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left: Config panel -->
        <div class="lg:col-span-1 space-y-5">

            <!-- Split configuration -->
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-on-surface mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-[20px]">tune</span>
                    Konfigurasi Split
                </h2>

                <form method="POST" action="<?= $baseUrl ?>/labeling/split" id="splitForm">
                    <input type="hidden" name="tahun" value="<?= $tahun ?>">

                    <!-- Ratio selector -->
                    <div class="mb-5">
                        <label class="block text-xs font-semibold text-on-surface-variant mb-2">Rasio Train / Test</label>
                        <div class="grid grid-cols-2 gap-2 mb-3">
                            <?php foreach ([[0.7,'70 / 30'],[0.8,'80 / 20'],[0.75,'75 / 25'],[0.9,'90 / 10']] as [$val,$lbl]): ?>
                            <label class="ratio-btn flex items-center justify-center gap-1.5 border rounded-lg px-3 py-2.5 cursor-pointer text-sm font-semibold transition-all
                                          <?= $val == 0.8 ? 'border-primary bg-primary/10 text-primary' : 'border-outline-variant text-on-surface-variant hover:border-primary/50' ?>"
                                   data-val="<?= $val ?>">
                                <input type="radio" name="train_ratio" value="<?= $val ?>" <?= $val==0.8?'checked':'' ?> class="hidden">
                                <?= $lbl ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="text-xs text-outline">Metode: Stratified Split — proporsi tiap kelas dipertahankan di kedua set.</p>
                    </div>

                    <!-- Live preview bar -->
                    <div class="mb-5 p-4 bg-surface-container rounded-xl">
                        <p class="text-xs text-outline font-semibold mb-2">Estimasi Distribusi (<span id="previewTotal"><?= $labeled ?></span> data)</p>
                        <div class="flex rounded-full overflow-hidden h-4 mb-2">
                            <div id="trainBar" class="bg-primary h-full transition-all" style="width:80%"></div>
                            <div id="testBar"  class="bg-secondary h-full transition-all" style="width:20%"></div>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="flex items-center gap-1.5 text-on-surface-variant">
                                <span class="w-3 h-3 rounded-full bg-primary inline-block"></span>
                                Train: <strong id="trainCount" class="text-on-surface"><?= round($labeled * 0.8) ?></strong>
                            </span>
                            <span class="flex items-center gap-1.5 text-on-surface-variant">
                                <span class="w-3 h-3 rounded-full bg-secondary inline-block"></span>
                                Test: <strong id="testCount" class="text-on-surface"><?= $labeled - round($labeled * 0.8) ?></strong>
                            </span>
                        </div>
                    </div>

                    <button type="button" onclick="confirmSplit()"
                            class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-primary text-white rounded-xl text-sm font-semibold hover:bg-on-primary-fixed-variant transition-colors shadow-sm">
                        <span class="material-symbols-outlined text-[18px]">call_split</span>
                        <?= $hasSplit ? 'Terapkan Ulang Split' : 'Terapkan Split' ?>
                    </button>
                </form>
            </div>

            <!-- Reset -->
            <?php if ($hasSplit): ?>
            <div class="bg-error-container/30 rounded-xl border border-error/20 p-4">
                <p class="text-xs font-semibold text-error mb-1">Reset Split</p>
                <p class="text-xs text-on-surface-variant mb-3">Hapus penandaan train/test dari seluruh data tahun <?= $tahun ?>.</p>
                <form method="POST" action="<?= $baseUrl ?>/labeling/split/reset"
                      data-confirm="Reset split data tahun <?= $tahun ?>? Semua penandaan train/test akan dihapus."
                      data-confirm-title="Reset Split" data-confirm-type="danger" data-confirm-ok="Reset">
                    <input type="hidden" name="tahun" value="<?= $tahun ?>">
                    <button type="submit" class="flex items-center gap-1.5 px-3 py-2 bg-error text-white rounded-lg text-xs font-semibold hover:opacity-90 transition-opacity">
                        <span class="material-symbols-outlined text-[16px]">restart_alt</span>
                        Reset Split
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right: Stats -->
        <div class="lg:col-span-2 space-y-5">

            <!-- Current split summary -->
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-on-surface mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-[20px]">bar_chart</span>
                    Status Split Saat Ini — Tahun <?= $tahun ?>
                </h2>

                <?php if (!$hasSplit): ?>
                <div class="flex items-center gap-3 p-4 bg-surface-container rounded-xl text-sm text-outline">
                    <span class="material-symbols-outlined text-[22px]">info</span>
                    Belum ada split yang diterapkan. Gunakan panel konfigurasi untuk memulai.
                </div>
                <?php else: ?>
                <!-- Global summary cards -->
                <div class="grid grid-cols-3 gap-3 mb-5">
                    <?php foreach ([
                        ['Total Berlabel', $labeled,      'text-primary',   'border-primary'],
                        ['Data Train',     $grandTrain,   'text-on-surface','border-on-surface'],
                        ['Data Test',      $grandTest,    'text-secondary', 'border-secondary'],
                    ] as [$lbl, $val, $cls, $border]): ?>
                    <div class="p-3 bg-surface-container rounded-xl border-l-4 <?= $border ?>">
                        <p class="text-[10px] text-outline font-semibold uppercase tracking-wider mb-0.5"><?= $lbl ?></p>
                        <p class="text-xl font-bold <?= $cls ?>"><?= $val ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Per-class breakdown -->
                <?php if (!empty($stats)): ?>
                <div class="space-y-4">
                    <?php foreach ($stats as $s):
                        $rl = $s['risk_label'];
                        $tot = (int)$s['total'];
                        $tr  = (int)$s['train'];
                        $te  = (int)$s['test'];
                        $un  = (int)$s['unassigned'];
                        $cls = $riskCls[$rl] ?? ['bar'=>'bg-primary','badge'=>'bg-primary/10 text-primary'];
                    ?>
                    <div class="p-4 bg-surface-container rounded-xl">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $cls['badge'] ?>"><?= $rl ?></span>
                                <span class="text-xs text-outline"><?= $tot ?> data total</span>
                            </div>
                            <?php if ($hasSplit && $tot > 0): ?>
                            <span class="text-xs text-outline">
                                Train <?= round($tr/$tot*100) ?>% · Test <?= round($te/$tot*100) ?>%
                            </span>
                            <?php endif; ?>
                        </div>
                        <?php if ($hasSplit && $tot > 0): ?>
                        <div class="flex rounded-full overflow-hidden h-3 mb-2">
                            <div class="<?= $cls['bar'] ?> h-full" style="width:<?= round($tr/$tot*100) ?>%; opacity:1"></div>
                            <div class="<?= $cls['bar'] ?> h-full" style="width:<?= round($te/$tot*100) ?>%; opacity:0.4"></div>
                            <?php if ($un > 0): ?><div class="bg-outline/20 h-full flex-1"></div><?php endif; ?>
                        </div>
                        <div class="flex items-center gap-4 text-xs text-on-surface-variant">
                            <span class="flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full <?= $cls['bar'] ?> inline-block"></span>
                                Train: <strong class="text-on-surface"><?= $tr ?></strong>
                            </span>
                            <span class="flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full <?= $cls['bar'] ?> opacity-40 inline-block"></span>
                                Test: <strong class="text-on-surface"><?= $te ?></strong>
                            </span>
                            <?php if ($un > 0): ?>
                            <span class="flex items-center gap-1.5 text-outline">
                                <span class="w-2.5 h-2.5 rounded-full bg-outline/30 inline-block"></span>
                                Unassigned: <?= $un ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="flex rounded-full overflow-hidden h-3 mb-1">
                            <div class="bg-outline/20 h-full w-full rounded-full"></div>
                        </div>
                        <p class="text-xs text-outline">Belum di-split</p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Info card -->
            <div class="bg-primary/5 border border-primary/20 rounded-xl p-4">
                <p class="text-xs font-semibold text-primary mb-1 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">lightbulb</span>
                    Tentang Stratified Split
                </p>
                <ul class="text-xs text-on-surface-variant space-y-1 list-disc list-inside">
                    <li>Setiap kelas (Rendah, Sedang, Tinggi) dibagi secara proporsional.</li>
                    <li>Data train digunakan untuk melatih model KNN.</li>
                    <li>Data test digunakan untuk evaluasi akurasi model.</li>
                    <li>Rekomendasi: rasio 80/20 untuk dataset &lt; 500 data.</li>
                </ul>
            </div>

        </div>
    </div>
    <?php endif; ?>

</main>

<?php require APP_PATH . '/Views/partials/toast.php'; ?>
<?php require APP_PATH . '/Views/partials/sidebar_script.php'; ?>
<script>
(function () {
    const total      = <?= $labeled ?>;
    const trainBar   = document.getElementById('trainBar');
    const testBar    = document.getElementById('testBar');
    const trainCount = document.getElementById('trainCount');
    const testCount  = document.getElementById('testCount');

    function updatePreview(ratio) {
        const tr = Math.round(total * ratio);
        const te = total - tr;
        if (trainBar) trainBar.style.width = (ratio * 100) + '%';
        if (testBar)  testBar.style.width  = ((1 - ratio) * 100) + '%';
        if (trainCount) trainCount.textContent = tr;
        if (testCount)  testCount.textContent  = te;
    }

    document.querySelectorAll('.ratio-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.ratio-btn').forEach(b => {
                b.classList.remove('border-primary','bg-primary/10','text-primary');
                b.classList.add('border-outline-variant','text-on-surface-variant');
            });
            btn.classList.add('border-primary','bg-primary/10','text-primary');
            btn.classList.remove('border-outline-variant','text-on-surface-variant');
            btn.querySelector('input[type=radio]').checked = true;
            updatePreview(parseFloat(btn.dataset.val));
        });
    });
})();

function confirmSplit() {
    const ratio = parseFloat(document.querySelector('input[name=train_ratio]:checked')?.value ?? 0.8);
    const tr    = Math.round(<?= $labeled ?> * ratio);
    const te    = <?= $labeled ?> - tr;
    const pct   = Math.round(ratio * 100) + '/' + Math.round((1 - ratio) * 100);
    const extra = <?= $hasSplit ? '"⚠️ Split sebelumnya akan ditimpa."' : '""' ?>;
    const msg   = `Terapkan stratified split ${pct}% untuk <?= $labeled ?> data berlabel:\n• Train: ${tr} data\n• Test: ${te} data` +
                  (extra ? '\n\n' + extra : '');
    showDialog({
        title:       'Terapkan Split Data',
        message:     msg,
        type:        <?= $hasSplit ? "'warning'" : "'info'" ?>,
        confirmText: 'Terapkan Split',
        onConfirm:   () => document.getElementById('splitForm').submit(),
    });
}
</script>
</body>
</html>
