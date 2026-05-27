<?php
$flash      = Flash::get();
$baseUrl    = BASE_URL;
$userName   = htmlspecialchars($_SESSION['user_name']  ?? 'User');
$userEmail  = htmlspecialchars($_SESSION['user_email'] ?? '');
$userRole   = $_SESSION['user_role'] ?? 'viewer';

$isEdit  = $isEdit ?? false;
$record  = $record ?? null;

// Partials config
$pageHeadTitle = $isEdit ? 'Edit Data Pemeliharaan' : 'Tambah Data Pemeliharaan';
$activeMenu    = 'pemeliharaan';
$pageTitle     = $pageHeadTitle;
$pageIcon      = 'engineering';
$backUrl       = $baseUrl . '/pemeliharaan';
$formAction    = $isEdit ? $baseUrl . '/pemeliharaan/edit/' . $record['id'] : $baseUrl . '/pemeliharaan/create';

$v = fn(string $key, $default = '') => htmlspecialchars((string) ($record[$key] ?? $default));

$namaBulan = ['','Januari','Februari','Maret','April','Mei','Juni',
              'Juli','Agustus','September','Oktober','November','Desember'];

$numericFields = [
    ['tier1_inpeksi',             'TIER 1 Inspeksi'],
    ['tier1_temuan',              'TIER 1 Temuan'],
    ['tier2_inpeksi',             'TIER 2 Inspeksi'],
    ['tier2_temuan',              'TIER 2 Temuan'],
    ['pengukuran',                'Pengukuran'],
    ['pergantian_fco',            'Pergantian FCO'],
    ['penyeimbangan_beban_gardu', 'Penyeimbangan Beban Gardu'],
    ['perbaikan_grounding_trafo', 'Perbaikan Grounding Trafo'],
    ['penghalang_panjat',         'Penghalang Panjat'],
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
    <div class="max-w-2xl mx-auto">
        <form method="POST" action="<?= $formAction ?>">
            <div class="bg-surface-container-lowest rounded-xl shadow-sm border border-outline-variant/30 overflow-hidden">

                <!-- Identitas -->
                <div class="p-5 sm:p-6 border-b border-outline-variant/30">
                    <h3 class="text-sm font-semibold text-on-surface mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[18px]">info</span>
                        Identitas Penyulang
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-3 space-y-1">
                            <label class="text-xs font-semibold text-outline uppercase tracking-wider">Nama Penyulang <span class="text-error">*</span></label>
                            <input type="text" name="penyulang" required value="<?= $v('penyulang') ?>"
                                   class="block w-full px-4 py-2.5 bg-surface-container-low border-0 rounded-xl text-sm text-on-surface ring-1 ring-inset ring-outline-variant focus:ring-2 focus:ring-primary focus:bg-white transition-all outline-none"
                                   placeholder="Contoh: PENYULANG ARJOSARI">
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-semibold text-outline uppercase tracking-wider">Bulan <span class="text-error">*</span></label>
                            <select name="bulan" required class="block w-full px-4 py-2.5 bg-surface-container-low border-0 rounded-xl text-sm text-on-surface ring-1 ring-inset ring-outline-variant focus:ring-2 focus:ring-primary outline-none">
                                <?php for ($b = 1; $b <= 12; $b++): ?>
                                <option value="<?= $b ?>" <?= ($record['bulan'] ?? 1) == $b ? 'selected' : '' ?>><?= $namaBulan[$b] ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-semibold text-outline uppercase tracking-wider">Tahun <span class="text-error">*</span></label>
                            <select name="tahun" required class="block w-full px-4 py-2.5 bg-surface-container-low border-0 rounded-xl text-sm text-on-surface ring-1 ring-inset ring-outline-variant focus:ring-2 focus:ring-primary outline-none">
                                <?php foreach ([2025, 2026, 2027] as $y): ?>
                                <option value="<?= $y ?>" <?= ($record['tahun'] ?? 2025) == $y ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Data Aktivitas -->
                <div class="p-5 sm:p-6">
                    <h3 class="text-sm font-semibold text-on-surface mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[18px]">construction</span>
                        Data Aktivitas Pemeliharaan
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <?php foreach ($numericFields as [$field, $label]): ?>
                        <div class="space-y-1">
                            <label class="text-xs font-semibold text-outline uppercase tracking-wider"><?= $label ?></label>
                            <input type="number" name="<?= $field ?>" min="0" value="<?= $v($field, 0) ?>"
                                   class="block w-full px-4 py-2.5 bg-surface-container-low border-0 rounded-xl text-sm text-on-surface ring-1 ring-inset ring-outline-variant focus:ring-2 focus:ring-primary focus:bg-white transition-all outline-none">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Actions -->
                <div class="px-5 sm:px-6 py-4 bg-surface-container-low border-t border-outline-variant/30 flex items-center justify-end gap-3">
                    <a href="<?= $baseUrl ?>/pemeliharaan"
                       class="px-5 py-2.5 text-sm font-semibold text-on-surface-variant hover:text-on-surface ring-1 ring-outline-variant hover:ring-outline rounded-xl transition-colors">
                        Batal
                    </a>
                    <button type="submit"
                            class="px-5 py-2.5 text-sm font-semibold bg-primary text-white rounded-xl hover:bg-on-primary-fixed-variant transition-colors shadow-sm flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]"><?= $isEdit ? 'save' : 'add_circle' ?></span>
                        <?= $isEdit ? 'Simpan Perubahan' : 'Tambah Data' ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</main>

<?php require APP_PATH . '/Views/partials/toast.php'; ?>
<?php require APP_PATH . '/Views/partials/sidebar_script.php'; ?>
</body>
</html>
