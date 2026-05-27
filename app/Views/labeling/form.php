<?php
$flash      = Flash::get();
$baseUrl    = BASE_URL;
$userName   = htmlspecialchars($_SESSION['user_name']  ?? 'User');
$userEmail  = htmlspecialchars($_SESSION['user_email'] ?? '');
$userRole   = $_SESSION['user_role'] ?? 'viewer';

$isEdit = $isEdit ?? false;
$record = $record ?? null;
$pem    = $pem    ?? [];

$namaBulan = ['','Januari','Februari','Maret','April','Mei','Juni',
              'Juli','Agustus','September','Oktober','November','Desember'];

$pageHeadTitle = $isEdit ? 'Edit Label FMEA' : 'Tambah Label FMEA';
$activeMenu    = 'labeling';
$pageTitle     = $pageHeadTitle;
$pageIcon      = 'label';
$backUrl       = $baseUrl . '/labeling';
$formAction    = $isEdit ? $baseUrl . '/labeling/edit/' . $record['id'] : $baseUrl . '/labeling/store';

$v = fn($k, $d='') => htmlspecialchars((string)($record[$k] ?? $d));

// FMEA reference table
$fmeaRef = [
    'severity' => [
        [1,  'Tidak ada efek'],
        [2,  'Efek sangat ringan, tidak mengganggu operasional'],
        [3,  'Efek ringan, gangguan minor'],
        [4,  'Efek sedang, penurunan performa ringan'],
        [5,  'Efek sedang, penurunan performa signifikan'],
        [6,  'Efek besar, gangguan layanan sebagian'],
        [7,  'Efek besar, gangguan layanan luas'],
        [8,  'Efek sangat besar, pemadaman terlokalisasi'],
        [9,  'Efek kritis, pemadaman luas'],
        [10, 'Efek katastrofik, kegagalan total sistem'],
    ],
    'occurrence' => [
        [1,  'Hampir tidak pernah terjadi'],
        [2,  'Sangat jarang (1x / 5 tahun)'],
        [3,  'Jarang (1x / 3 tahun)'],
        [4,  'Kadang (1x / tahun)'],
        [5,  'Cukup sering (beberapa kali / tahun)'],
        [6,  'Sering (1x / bulan)'],
        [7,  'Sangat sering (beberapa kali / bulan)'],
        [8,  'Berulang (mingguan)'],
        [9,  'Hampir selalu'],
        [10, 'Selalu terjadi'],
    ],
    'detection' => [
        [1,  'Pasti terdeteksi sebelum berdampak'],
        [2,  'Sangat mudah dideteksi'],
        [3,  'Mudah dideteksi'],
        [4,  'Cukup mudah dideteksi'],
        [5,  'Deteksi sedang, perlu inspeksi'],
        [6,  'Agak sulit dideteksi'],
        [7,  'Sulit dideteksi, butuh alat khusus'],
        [8,  'Sangat sulit dideteksi'],
        [9,  'Hampir tidak bisa dideteksi'],
        [10, 'Tidak terdeteksi sama sekali'],
    ],
];

$failureModes = [
    'Kegagalan isolasi trafo',
    'Overload beban melebihi kapasitas',
    'Korosi pada konektor',
    'Kerusakan akibat petir / surja tegangan',
    'Kegagalan FCO (Fuse Cut Out)',
    'Gangguan grounding tidak memadai',
    'Vegetasi menghalangi jaringan',
    'Kerusakan mekanik tiang / konduktor',
    'Gangguan akibat hewan liar',
    'Kelembaban tinggi menyebabkan gangguan',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php require APP_PATH . '/Views/partials/head.php'; ?>
    <style>
        input[type=range] { -webkit-appearance: none; appearance: none; width: 100%; height: 6px; border-radius: 9999px; outline: none; cursor: pointer; }
        input[type=range]::-webkit-slider-thumb { -webkit-appearance: none; width: 18px; height: 18px; border-radius: 50%; background: #006492; cursor: pointer; box-shadow: 0 1px 4px rgba(0,0,0,.2); }
        input[type=range].s-track { background: linear-gradient(to right, #006492 0%, #006492 var(--pct,10%), #e5eeff var(--pct,10%), #e5eeff 100%); }
        input[type=range].o-track { background: linear-gradient(to right, #1b60a2 0%, #1b60a2 var(--pct,10%), #e5eeff var(--pct,10%), #e5eeff 100%); }
        input[type=range].d-track { background: linear-gradient(to right, #576065 0%, #576065 var(--pct,10%), #e5eeff var(--pct,10%), #e5eeff 100%); }
    </style>
</head>
<body class="bg-background text-on-background">

<?php require APP_PATH . '/Views/partials/sidebar.php'; ?>
<?php require APP_PATH . '/Views/partials/header.php'; ?>

<main class="ml-0 md:ml-64 mt-16 p-4 sm:p-6 min-h-screen">
<div class="max-w-3xl mx-auto space-y-5">

    <!-- Info penyulang -->
    <div class="bg-primary p-5 rounded-xl text-white flex flex-wrap items-center gap-4 relative overflow-hidden">
        <div class="absolute -right-6 -top-6 w-24 h-24 bg-white/10 rounded-full pointer-events-none"></div>
        <span class="material-symbols-outlined text-[40px] text-white/80" style="font-variation-settings:'FILL' 1;">electric_bolt</span>
        <div>
            <p class="text-xs text-primary-fixed/80 uppercase tracking-wider font-semibold">Penyulang yang Dilabeli</p>
            <p class="text-xl font-bold"><?= htmlspecialchars($pem['penyulang'] ?? '') ?></p>
            <p class="text-sm text-primary-fixed/90 mt-0.5">
                <?= $namaBulan[$pem['bulan'] ?? 0] ?? '' ?> <?= $pem['tahun'] ?? '' ?>
            </p>
        </div>
    </div>

    <form method="POST" action="<?= $formAction ?>">
        <input type="hidden" name="pemeliharaan_id" value="<?= (int)($pem['id'] ?? 0) ?>">

        <!-- Mode Kegagalan -->
        <div class="bg-surface-container-lowest rounded-xl shadow-sm border border-outline-variant/30 p-5 sm:p-6 space-y-4">
            <h3 class="text-sm font-semibold text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-[18px]">report_problem</span>
                Mode Kegagalan
            </h3>
            <div class="space-y-1">
                <label class="text-xs font-semibold text-outline uppercase tracking-wider">Mode Kegagalan yang Teridentifikasi <span class="text-error">*</span></label>
                <input list="failure-list" name="failure_mode" required
                       value="<?= $v('failure_mode') ?>"
                       class="block w-full px-4 py-2.5 bg-surface-container-low border-0 rounded-xl text-sm text-on-surface ring-1 ring-inset ring-outline-variant focus:ring-2 focus:ring-primary focus:bg-white transition-all outline-none"
                       placeholder="Contoh: Kegagalan isolasi trafo akibat beban berlebih">
                <datalist id="failure-list">
                    <?php foreach ($failureModes as $fm): ?>
                    <option value="<?= htmlspecialchars($fm) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="space-y-1">
                <label class="text-xs font-semibold text-outline uppercase tracking-wider">Catatan Tambahan</label>
                <textarea name="catatan" rows="2"
                          class="block w-full px-4 py-2.5 bg-surface-container-low border-0 rounded-xl text-sm text-on-surface ring-1 ring-inset ring-outline-variant focus:ring-2 focus:ring-primary focus:bg-white transition-all outline-none resize-none"
                          placeholder="Catatan tambahan (opsional)"><?= $v('catatan') ?></textarea>
            </div>
        </div>

        <!-- FMEA Sliders -->
        <div class="bg-surface-container-lowest rounded-xl shadow-sm border border-outline-variant/30 p-5 sm:p-6 space-y-6 mt-5">
            <h3 class="text-sm font-semibold text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-[18px]">tune</span>
                Penilaian FMEA <span class="text-xs text-outline font-normal">(masing-masing 1–10)</span>
            </h3>

            <?php foreach ([
                ['s', 'severity',   'Severity (S)',   'Keparahan dampak kegagalan',          's-track', 'text-primary',   '#006492'],
                ['o', 'occurrence', 'Occurrence (O)', 'Frekuensi / kemungkinan kegagalan',    'o-track', 'text-secondary', '#1b60a2'],
                ['d', 'detection',  'Detection (D)',  'Kesulitan mendeteksi kegagalan (10=tidak terdeteksi)', 'd-track', 'text-tertiary',  '#576065'],
            ] as [$key, $name, $label, $desc, $trackCls, $valCls, $color]): ?>
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <div>
                        <label class="text-sm font-semibold text-on-surface"><?= $label ?></label>
                        <p class="text-xs text-outline"><?= $desc ?></p>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span id="<?= $key ?>-val"
                              class="text-2xl font-bold <?= $valCls ?> w-10 text-right tabular-nums"><?= $v($name, 1) ?></span>
                        <span class="text-xs text-outline">/ 10</span>
                    </div>
                </div>
                <input type="range" name="<?= $name ?>" id="<?= $key ?>-range"
                       min="1" max="10" value="<?= $v($name, 1) ?>"
                       class="<?= $trackCls ?>"
                       oninput="updateSlider('<?= $key ?>', this.value, '<?= $color ?>')">
                <!-- Reference row -->
                <p id="<?= $key ?>-ref" class="text-xs text-outline italic min-h-[16px]">
                    <?php $ref = $fmeaRef[$name][(int)$v($name, 1) - 1] ?? null; echo $ref ? $ref[1] : ''; ?>
                </p>
            </div>
            <?php endforeach; ?>

            <!-- RPN Result -->
            <div class="mt-2 p-4 rounded-xl border-2 border-dashed" id="rpn-box" style="border-color:#006492;background:#eff4ff">
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <div>
                        <p class="text-xs text-outline font-semibold uppercase tracking-wider">Risk Priority Number (RPN)</p>
                        <p class="text-xs text-outline mt-0.5">S × O × D = <span id="rpn-formula" class="font-mono">1 × 1 × 1</span></p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span id="rpn-value" class="text-4xl font-extrabold text-primary tabular-nums">1</span>
                        <div>
                            <span id="rpn-badge" class="px-3 py-1 rounded-full text-sm font-bold bg-green-100 text-green-800">Low Risk</span>
                            <p id="rpn-hint" class="text-[10px] text-outline mt-1 text-right">RPN ≤ 100</p>
                        </div>
                    </div>
                </div>
                <div class="mt-3 w-full h-2 bg-white/60 rounded-full overflow-hidden">
                    <div id="rpn-bar" class="h-full rounded-full bg-green-500 transition-all duration-300" style="width:0.1%"></div>
                </div>
            </div>

            <!-- Override label -->
            <div class="space-y-1">
                <label class="text-xs font-semibold text-outline uppercase tracking-wider">Override Risk Label <span class="text-outline font-normal">(opsional — kosongkan untuk otomatis)</span></label>
                <select name="risk_label_override" class="block w-full px-4 py-2.5 bg-surface-container-low border-0 rounded-xl text-sm text-on-surface ring-1 ring-inset ring-outline-variant focus:ring-2 focus:ring-primary outline-none">
                    <option value="">— Otomatis berdasarkan RPN —</option>
                    <option value="Rendah" <?= ($record['risk_label'] ?? '') === 'Rendah' ? 'selected' : '' ?>>Rendah (RPN ≤ 100)</option>
                    <option value="Sedang" <?= ($record['risk_label'] ?? '') === 'Sedang' ? 'selected' : '' ?>>Sedang (RPN 101–200)</option>
                    <option value="Tinggi" <?= ($record['risk_label'] ?? '') === 'Tinggi' ? 'selected' : '' ?>>Tinggi (RPN > 200)</option>
                </select>
            </div>
        </div>

        <!-- Reference table (collapsible) -->
        <details class="bg-surface-container-lowest rounded-xl shadow-sm border border-outline-variant/30 overflow-hidden mt-5">
            <summary class="px-5 py-4 cursor-pointer text-sm font-semibold text-on-surface flex items-center gap-2 select-none hover:bg-surface-container-low transition-colors">
                <span class="material-symbols-outlined text-secondary text-[18px]">menu_book</span>
                Panduan Skala FMEA
            </summary>
            <div class="px-5 pb-5 grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                <?php foreach ($fmeaRef as $dim => $rows): ?>
                <div>
                    <p class="font-semibold text-on-surface uppercase tracking-wider mb-2"><?= ucfirst($dim) ?></p>
                    <div class="space-y-1">
                        <?php foreach ($rows as [$val, $desc]): ?>
                        <div class="flex gap-2">
                            <span class="font-bold text-primary w-4 text-right flex-shrink-0"><?= $val ?></span>
                            <span class="text-outline leading-tight"><?= $desc ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </details>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3 mt-5">
            <a href="<?= $baseUrl ?>/labeling"
               class="px-5 py-2.5 text-sm font-semibold text-on-surface-variant ring-1 ring-outline-variant hover:ring-outline rounded-xl transition-colors">
                Batal
            </a>
            <button type="submit"
                    class="px-6 py-2.5 text-sm font-semibold bg-primary text-white rounded-xl hover:bg-on-primary-fixed-variant transition-colors shadow-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]"><?= $isEdit ? 'save' : 'label' ?></span>
                <?= $isEdit ? 'Simpan Perubahan' : 'Simpan Label' ?>
            </button>
        </div>
    </form>
</div>
</main>

<?php require APP_PATH . '/Views/partials/toast.php'; ?>
<?php require APP_PATH . '/Views/partials/sidebar_script.php'; ?>

<script>
const fmeaRef = <?= json_encode($fmeaRef) ?>;

function updateSlider(key, val, color) {
    val = parseInt(val);
    const pct = ((val - 1) / 9 * 100).toFixed(1);

    // Update display
    document.getElementById(key + '-val').textContent = val;

    // Update track fill
    const range = document.getElementById(key + '-range');
    range.style.setProperty('--pct', pct + '%');

    // Update reference text
    const dimMap = { s: 'severity', o: 'occurrence', d: 'detection' };
    const refs   = fmeaRef[dimMap[key]];
    document.getElementById(key + '-ref').textContent = refs[val - 1]?.[1] ?? '';

    recalcRPN();
}

function recalcRPN() {
    const s = parseInt(document.getElementById('s-range').value);
    const o = parseInt(document.getElementById('o-range').value);
    const d = parseInt(document.getElementById('d-range').value);
    const rpn = s * o * d;

    document.getElementById('rpn-value').textContent   = rpn;
    document.getElementById('rpn-formula').textContent  = `${s} × ${o} × ${d}`;

    const badge = document.getElementById('rpn-badge');
    const bar   = document.getElementById('rpn-bar');
    const box   = document.getElementById('rpn-box');
    const hint  = document.getElementById('rpn-hint');
    const pct   = Math.min(100, (rpn / 1000 * 100)).toFixed(1);
    bar.style.width = pct + '%';

    if (rpn <= 100) {
        badge.className = 'px-3 py-1 rounded-full text-sm font-bold bg-green-100 text-green-800';
        badge.textContent = 'Rendah';
        bar.className = 'h-full rounded-full bg-green-500 transition-all duration-300';
        box.style.borderColor = '#22c55e'; box.style.background = '#f0fdf4';
        hint.textContent = 'RPN ≤ 100';
        document.getElementById('rpn-value').className = 'text-4xl font-extrabold text-green-600 tabular-nums';
    } else if (rpn <= 200) {
        badge.className = 'px-3 py-1 rounded-full text-sm font-bold bg-amber-100 text-amber-800';
        badge.textContent = 'Sedang';
        bar.className = 'h-full rounded-full bg-amber-500 transition-all duration-300';
        box.style.borderColor = '#f59e0b'; box.style.background = '#fffbeb';
        hint.textContent = 'RPN 101–200';
        document.getElementById('rpn-value').className = 'text-4xl font-extrabold text-amber-600 tabular-nums';
    } else {
        badge.className = 'px-3 py-1 rounded-full text-sm font-bold bg-red-100 text-red-800';
        badge.textContent = 'Tinggi';
        bar.className = 'h-full rounded-full bg-red-500 transition-all duration-300';
        box.style.borderColor = '#ef4444'; box.style.background = '#fef2f2';
        hint.textContent = 'RPN > 200';
        document.getElementById('rpn-value').className = 'text-4xl font-extrabold text-red-600 tabular-nums';
    }
}

// Init on load
['s','o','d'].forEach(k => {
    const range = document.getElementById(k + '-range');
    const pct   = ((parseInt(range.value) - 1) / 9 * 100).toFixed(1);
    range.style.setProperty('--pct', pct + '%');
});
recalcRPN();
</script>
</body>
</html>
