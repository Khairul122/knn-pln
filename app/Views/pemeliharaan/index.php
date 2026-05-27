<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$flash      = Flash::get();
$baseUrl    = BASE_URL;
$userName   = htmlspecialchars($_SESSION['user_name']  ?? 'User');
$userEmail  = htmlspecialchars($_SESSION['user_email'] ?? '');
$userRole   = $_SESSION['user_role'] ?? 'viewer';

$namaBulan = ['','Januari','Februari','Maret','April','Mei','Juni',
              'Juli','Agustus','September','Oktober','November','Desember'];

// Partials config
$pageHeadTitle = 'Data Pemeliharaan';
$activeMenu    = 'pemeliharaan';
$pageTitle     = 'Data Pemeliharaan';
$pageIcon      = 'engineering';
$headerActions = null;

function qStr(array $merge = []): string {
    global $tahun, $search, $page;
    $p = array_merge(['tahun' => $tahun, 'search' => $search, 'page' => $page], $merge);
    return '?' . http_build_query(array_filter($p, fn($v) => $v !== '' && $v !== null));
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

    <!-- Filter bar -->
    <form method="GET" action="<?= $baseUrl ?>/pemeliharaan"
          class="flex flex-wrap gap-3 mb-6 bg-surface-container-lowest p-4 rounded-xl shadow-sm border border-outline-variant/30">
        <div class="flex items-center gap-2 flex-1 min-w-[160px]">
            <span class="material-symbols-outlined text-outline text-[18px]">search</span>
            <input name="search" value="<?= htmlspecialchars($search) ?>"
                   class="bg-transparent border-none focus:ring-0 text-sm w-full outline-none text-on-surface placeholder:text-outline"
                   placeholder="Cari penyulang..." type="text">
        </div>
        <div class="flex items-center gap-3">
            <label class="text-xs text-outline font-medium">Tahun:</label>
            <select name="tahun" class="text-sm border border-outline-variant rounded-lg pl-3 pr-8 py-1.5 bg-white text-on-surface focus:ring-1 focus:ring-primary outline-none w-24">
                <?php foreach ($years as $y): ?>
                <option value="<?= $y ?>" <?= $y == $tahun ? 'selected' : '' ?>><?= $y ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="px-4 py-1.5 bg-primary text-white rounded-lg text-sm font-semibold hover:bg-on-primary-fixed-variant transition-colors">Filter</button>
        <?php if ($search): ?>
        <a href="<?= $baseUrl ?>/pemeliharaan<?= qStr(['search'=>'','page'=>1]) ?>"
           class="px-3 py-1.5 text-sm text-outline hover:text-on-surface transition-colors">Reset</a>
        <?php endif; ?>
    </form>

    <!-- Summary + action -->
    <div class="flex items-center justify-between mb-3">
        <p class="text-sm text-on-surface-variant">
            Menampilkan <strong class="text-on-surface"><?= count($items) ?></strong>
            dari <strong class="text-on-surface"><?= number_format($total) ?></strong> data
            <?= $search ? '· pencarian "<em>' . htmlspecialchars($search) . '</em>"' : '' ?>
        </p>
        <a href="<?= $baseUrl ?>/pemeliharaan/create"
           class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold bg-primary text-white hover:bg-on-primary-fixed-variant transition-colors shadow-sm">
            <span class="material-symbols-outlined text-[18px]">add</span>
            Tambah Data
        </a>
    </div>

    <!-- Table -->
    <div class="bg-surface-container-lowest rounded-xl shadow-sm border border-outline-variant/30 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left min-w-[900px]">
                <thead class="bg-surface-container-low border-b border-outline-variant">
                    <tr>
                        <?php foreach (['#','Penyulang','Bulan','T1 Insp','T1 Temuan','T2 Insp','T2 Temuan','Ukur','FCO','Beban','Ground','Panjat','Aksi'] as $h): ?>
                        <th class="px-4 py-3 text-[11px] font-semibold text-outline uppercase tracking-wider <?= in_array($h,['#','Aksi','Bulan','T1 Insp','T1 Temuan','T2 Insp','T2 Temuan','Ukur','FCO','Beban','Ground','Panjat']) ? 'text-center' : '' ?> <?= $h==='Penyulang'?'text-left':'' ?>"><?= $h ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="13" class="px-4 py-12 text-center text-outline text-sm">
                            <span class="material-symbols-outlined text-[48px] block mb-2 opacity-30">inbox</span>
                            Belum ada data pemeliharaan<?= $search ? ' yang cocok' : '' ?>.
                            <?php if (!$search): ?>
                            <a href="<?= $baseUrl ?>/pemeliharaan/import" class="text-primary font-semibold hover:underline ml-1">Import data Excel</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php $no = $offset + 1; foreach ($items as $row): ?>
                    <tr class="hover:bg-surface-container-low/60 transition-colors group">
                        <td class="px-4 py-3 text-xs text-outline text-center"><?= $no++ ?></td>
                        <td class="px-4 py-3 font-semibold text-sm text-on-surface"><?= htmlspecialchars($row['penyulang']) ?></td>
                        <td class="px-4 py-3 text-center text-sm text-on-surface-variant"><?= $namaBulan[$row['bulan']] ?? $row['bulan'] ?></td>
                        <?php foreach (['tier1_inpeksi','tier1_temuan','tier2_inpeksi','tier2_temuan','pengukuran','pergantian_fco','penyeimbangan_beban_gardu','perbaikan_grounding_trafo','penghalang_panjat'] as $col): ?>
                        <td class="px-4 py-3 text-center text-sm <?= $row[$col] > 0 ? 'text-on-surface font-medium' : 'text-outline' ?>"><?= $row[$col] ?></td>
                        <?php endforeach; ?>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="<?= $baseUrl ?>/pemeliharaan/edit/<?= $row['id'] ?>"
                                   class="p-1.5 rounded-lg text-primary hover:bg-primary-fixed transition-colors" title="Edit">
                                    <span class="material-symbols-outlined text-[18px]">edit</span>
                                </a>
                                <form method="POST" action="<?= $baseUrl ?>/pemeliharaan/delete/<?= $row['id'] ?>"
                                      data-confirm="Hapus data pemeliharaan ini? Tindakan tidak dapat dibatalkan."
                                      data-confirm-title="Hapus Data" data-confirm-type="danger" data-confirm-ok="Hapus">
                                    <button type="submit" class="p-1.5 rounded-lg text-error hover:bg-error-container transition-colors" title="Hapus">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="px-4 py-3 border-t border-outline-variant/30 flex items-center justify-between gap-3 flex-wrap">
            <p class="text-xs text-outline">Total <?= number_format($total) ?> data</p>
            <div class="flex items-center gap-1">
                <?php if ($page > 1): ?>
                <a href="<?= $baseUrl ?>/pemeliharaan<?= qStr(['page' => $page - 1]) ?>"
                   class="px-3 py-1.5 text-sm rounded-lg text-primary hover:bg-primary-fixed transition-colors font-medium flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px]">chevron_left</span> Prev
                </a>
                <?php endif; ?>
                <?php for ($p = max(1, $page-2); $p <= min($totalPages, $page+2); $p++): ?>
                <a href="<?= $baseUrl ?>/pemeliharaan<?= qStr(['page' => $p]) ?>"
                   class="w-8 h-8 flex items-center justify-center text-sm rounded-lg transition-colors font-medium <?= $p===$page?'bg-primary text-white':'text-on-surface-variant hover:bg-surface-container' ?>">
                    <?= $p ?>
                </a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                <a href="<?= $baseUrl ?>/pemeliharaan<?= qStr(['page' => $page + 1]) ?>"
                   class="px-3 py-1.5 text-sm rounded-lg text-primary hover:bg-primary-fixed transition-colors font-medium flex items-center gap-1">
                    Next <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php require APP_PATH . '/Views/partials/toast.php'; ?>
<?php require APP_PATH . '/Views/partials/sidebar_script.php'; ?>
</body>
</html>
