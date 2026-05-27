<?php
$flash      = Flash::get();
$baseUrl    = BASE_URL;
$userName   = htmlspecialchars($_SESSION['user_name']  ?? 'User');
$userEmail  = htmlspecialchars($_SESSION['user_email'] ?? '');
$userRole   = $_SESSION['user_role'] ?? 'viewer';

$namaBulan  = ['','Januari','Februari','Maret','April','Mei','Juni',
               'Juli','Agustus','September','Oktober','November','Desember'];

$pageHeadTitle = 'Labeling FMEA';
$activeMenu    = 'labeling';
$pageTitle     = 'Labeling FMEA';
$pageIcon      = 'label';
$headerActions = null;

function labelQ(array $merge = []): string {
    global $tahun, $search, $status, $label, $page;
    $p = array_merge(['tahun'=>$tahun,'search'=>$search,'status'=>$status,'label'=>$label,'page'=>$page], $merge);
    return '?' . http_build_query(array_filter($p, fn($v) => $v !== '' && $v !== null));
}

$riskCls = [
    'Rendah' => 'bg-green-100 text-green-800',
    'Sedang' => 'bg-amber-100 text-amber-800',
    'Tinggi' => 'bg-red-100   text-red-800',
];
$riskDot = ['Rendah'=>'bg-green-500','Sedang'=>'bg-amber-500','Tinggi'=>'bg-red-500'];
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

    <!-- Action bar top row -->
    <div class="flex items-center justify-between gap-3 mb-4">
        <div></div>
        <a href="<?= $baseUrl ?>/labeling/split<?= $tahun ? '?tahun='.$tahun : '' ?>"
           class="flex items-center gap-2 px-4 py-2.5 bg-secondary text-white rounded-xl text-sm font-semibold hover:opacity-90 transition-opacity shadow-sm">
            <span class="material-symbols-outlined text-[18px]">call_split</span>
            Split Data
        </a>
    </div>

    <!-- Auto-label action bar -->
    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30 shadow-sm px-5 py-4 mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-start gap-3">
            <span class="material-symbols-outlined text-primary text-[24px] mt-0.5" style="font-variation-settings:'FILL' 1;">auto_awesome</span>
            <div>
                <p class="text-sm font-semibold text-on-surface">Labeling Otomatis FMEA</p>
                <p class="text-xs text-outline mt-0.5">Hitung S, O, D, dan RPN secara otomatis dari seluruh data pemeliharaan tahun <?= $tahun ?>.</p>
            </div>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            <form method="POST" action="<?= $baseUrl ?>/labeling/auto-label" id="autoLabelForm">
                <input type="hidden" name="tahun" value="<?= $tahun ?>">
                <input type="hidden" name="overwrite" id="overwriteInput" value="0">
                <div class="flex items-center gap-2">
                    <label class="flex items-center gap-1.5 text-xs text-on-surface-variant cursor-pointer select-none">
                        <input type="checkbox" id="overwriteCheck"
                               class="rounded border-outline-variant text-primary focus:ring-primary cursor-pointer"
                               onchange="document.getElementById('overwriteInput').value = this.checked ? '1' : '0'">
                        Timpa label yang sudah ada
                    </label>
                    <button type="button" onclick="confirmAutoLabel()"
                            class="flex items-center gap-2 px-4 py-2.5 bg-primary text-white rounded-xl text-sm font-semibold hover:bg-on-primary-fixed-variant transition-colors shadow-sm">
                        <span class="material-symbols-outlined text-[18px]">auto_awesome</span>
                        Label Semua Otomatis
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary cards -->
    <?php
    $labeled   = (int)($summary['labeled']  ?? 0);
    $total_all = (int)($summary['total']    ?? 0);
    $unlabeled = $total_all - $labeled;
    $pct       = $total_all > 0 ? round($labeled / $total_all * 100) : 0;
    ?>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
        <div class="col-span-2 sm:col-span-3 lg:col-span-2 bg-surface-container-lowest p-5 rounded-xl custom-shadow border-l-4 border-primary">
            <p class="text-xs text-outline font-semibold uppercase tracking-wider mb-1">Progress Labeling</p>
            <div class="flex items-end gap-2 mb-2">
                <span class="text-2xl font-bold text-primary"><?= $labeled ?></span>
                <span class="text-sm text-outline mb-0.5">/ <?= $total_all ?> data</span>
            </div>
            <div class="w-full h-2 bg-surface-container rounded-full overflow-hidden">
                <div class="h-full bg-primary rounded-full transition-all" style="width:<?= $pct ?>%"></div>
            </div>
            <p class="text-xs text-outline mt-1"><?= $pct ?>% selesai · <?= $unlabeled ?> belum berlabel</p>
        </div>
        <?php foreach ([
            ['Risiko Rendah', $summary['low']    ?? 0, 'bg-green-500', 'border-green-400', 'text-green-700'],
            ['Risiko Sedang', $summary['medium'] ?? 0, 'bg-amber-500', 'border-amber-400', 'text-amber-700'],
            ['Risiko Tinggi', $summary['high']   ?? 0, 'bg-red-500',   'border-red-400',   'text-red-700'],
            ['Avg RPN',     $summary['avg_rpn'] ?? 0,'bg-secondary', 'border-secondary',  'text-secondary'],
            ['Max RPN',     $summary['max_rpn'] ?? 0,'bg-primary',   'border-primary',     'text-primary'],
        ] as [$lbl, $val, $dot, $border, $text]): ?>
        <div class="bg-surface-container-lowest p-4 rounded-xl custom-shadow border-l-4 <?= $border ?>">
            <p class="text-xs text-outline font-semibold uppercase tracking-wider mb-1"><?= $lbl ?></p>
            <p class="text-2xl font-bold <?= $text ?>"><?= $val ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Filter -->
    <form method="GET" action="<?= $baseUrl ?>/labeling"
          class="flex flex-wrap gap-3 mb-5 bg-surface-container-lowest p-4 rounded-xl shadow-sm border border-outline-variant/30">
        <div class="flex items-center gap-2 flex-1 min-w-[140px]">
            <span class="material-symbols-outlined text-outline text-[18px]">search</span>
            <input name="search" value="<?= htmlspecialchars($search) ?>"
                   class="bg-transparent border-none focus:ring-0 text-sm w-full outline-none text-on-surface placeholder:text-outline"
                   placeholder="Cari penyulang..." type="text">
        </div>
        <select name="tahun" class="text-sm border border-outline-variant rounded-lg pl-3 pr-8 py-1.5 bg-white text-on-surface focus:ring-1 focus:ring-primary outline-none w-28">
            <?php foreach ($years as $y): ?>
            <option value="<?= $y ?>" <?= $y == $tahun ? 'selected' : '' ?>><?= $y ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status" class="text-sm border border-outline-variant rounded-lg pl-3 pr-8 py-1.5 bg-white text-on-surface focus:ring-1 focus:ring-primary outline-none w-40">
            <option value="" <?= $status==='' ? 'selected':'' ?>>Semua Status</option>
            <option value="labeled"   <?= $status==='labeled'   ? 'selected':'' ?>>Sudah Berlabel</option>
            <option value="unlabeled" <?= $status==='unlabeled' ? 'selected':'' ?>>Belum Berlabel</option>
        </select>
        <select name="label" class="text-sm border border-outline-variant rounded-lg pl-3 pr-8 py-1.5 bg-white text-on-surface focus:ring-1 focus:ring-primary outline-none w-40">
            <option value="" <?= $label==='' ? 'selected':'' ?>>Semua Risiko</option>
            <option value="Rendah" <?= $label==='Rendah' ? 'selected':'' ?>>Risiko Rendah</option>
            <option value="Sedang" <?= $label==='Sedang' ? 'selected':'' ?>>Risiko Sedang</option>
            <option value="Tinggi" <?= $label==='Tinggi' ? 'selected':'' ?>>Risiko Tinggi</option>
        </select>
        <button type="submit" class="px-4 py-1.5 bg-primary text-white rounded-lg text-sm font-semibold hover:bg-on-primary-fixed-variant transition-colors">Filter</button>
    </form>

    <!-- Summary bar -->
    <div class="flex items-center justify-between mb-3">
        <p class="text-sm text-on-surface-variant">
            Menampilkan <strong class="text-on-surface"><?= count($items) ?></strong>
            dari <strong class="text-on-surface"><?= number_format($total) ?></strong> data
        </p>
        <p class="text-xs text-outline">Halaman <?= $page ?> / <?= max(1, $totalPages) ?></p>
    </div>

    <!-- Table -->
    <div class="bg-surface-container-lowest rounded-xl shadow-sm border border-outline-variant/30 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left min-w-[780px]">
                <thead class="bg-surface-container-low border-b border-outline-variant">
                    <tr>
                        <th class="px-4 py-3 text-[11px] font-semibold text-outline uppercase tracking-wider w-8">#</th>
                        <th class="px-4 py-3 text-[11px] font-semibold text-outline uppercase tracking-wider">Penyulang</th>
                        <th class="px-4 py-3 text-[11px] font-semibold text-outline uppercase tracking-wider text-center">Bulan</th>
                        <th class="px-4 py-3 text-[11px] font-semibold text-outline uppercase tracking-wider">Mode Kegagalan</th>
                        <th class="px-4 py-3 text-[11px] font-semibold text-outline uppercase tracking-wider text-center">S</th>
                        <th class="px-4 py-3 text-[11px] font-semibold text-outline uppercase tracking-wider text-center">O</th>
                        <th class="px-4 py-3 text-[11px] font-semibold text-outline uppercase tracking-wider text-center">D</th>
                        <th class="px-4 py-3 text-[11px] font-semibold text-outline uppercase tracking-wider text-center">RPN</th>
                        <th class="px-4 py-3 text-[11px] font-semibold text-outline uppercase tracking-wider text-center">Risk Label</th>
                        <th class="px-4 py-3 text-[11px] font-semibold text-outline uppercase tracking-wider text-center">Split</th>
                        <th class="px-4 py-3 text-[11px] font-semibold text-outline uppercase tracking-wider text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="11" class="px-4 py-12 text-center text-outline text-sm">
                            <span class="material-symbols-outlined text-[48px] block mb-2 opacity-30">label_off</span>
                            Tidak ada data yang cocok.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php $no = $offset + 1; foreach ($items as $row): $hasLabel = !empty($row['label_id']); ?>
                    <tr class="hover:bg-surface-container-low/50 transition-colors group">
                        <td class="px-4 py-3 text-xs text-outline text-center"><?= $no++ ?></td>
                        <td class="px-4 py-3 font-semibold text-sm text-on-surface"><?= htmlspecialchars($row['penyulang']) ?></td>
                        <td class="px-4 py-3 text-center text-sm text-on-surface-variant"><?= $namaBulan[$row['bulan']] ?? $row['bulan'] ?></td>
                        <td class="px-4 py-3 text-sm text-on-surface-variant max-w-[180px] truncate">
                            <?= $hasLabel ? htmlspecialchars($row['failure_mode']) : '<span class="text-outline italic text-xs">— belum diisi —</span>' ?>
                        </td>
                        <?php foreach (['severity','occurrence','detection'] as $sod): ?>
                        <td class="px-4 py-3 text-center text-sm <?= $hasLabel ? 'font-semibold text-on-surface' : 'text-outline' ?>">
                            <?= $hasLabel ? $row[$sod] : '—' ?>
                        </td>
                        <?php endforeach; ?>
                        <td class="px-4 py-3 text-center">
                            <?php if ($hasLabel): ?>
                            <?php $rpnCls = $row['rpn'] > 200 ? 'text-red-700 bg-red-50' : ($row['rpn'] > 100 ? 'text-amber-700 bg-amber-50' : 'text-green-700 bg-green-50'); ?>
                            <span class="px-2 py-0.5 rounded-md text-xs font-bold <?= $rpnCls ?>"><?= $row['rpn'] ?></span>
                            <?php else: ?><span class="text-outline text-xs">—</span><?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($hasLabel): ?>
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= $riskCls[$row['risk_label']] ?? '' ?>">
                                <?= $row['risk_label'] ?>
                            </span>
                            <?php else: ?>
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-surface-container text-outline">Belum Berlabel</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($hasLabel && !empty($row['split_type'])): ?>
                                <?php if ($row['split_type'] === 'train'): ?>
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary">Train</span>
                                <?php else: ?>
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-secondary/10 text-secondary">Test</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-outline text-xs">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <?php if ($hasLabel): ?>
                                <a href="<?= $baseUrl ?>/labeling/edit/<?= $row['label_id'] ?>"
                                   class="p-1.5 rounded-lg text-primary hover:bg-primary-fixed transition-colors" title="Edit">
                                    <span class="material-symbols-outlined text-[18px]">edit</span>
                                </a>
                                <form method="POST" action="<?= $baseUrl ?>/labeling/delete/<?= $row['label_id'] ?>"
                                      data-confirm="Hapus label FMEA ini? Tindakan tidak dapat dibatalkan."
                                      data-confirm-title="Hapus Label" data-confirm-type="danger" data-confirm-ok="Hapus">
                                    <button class="p-1.5 rounded-lg text-error hover:bg-error-container transition-colors" title="Hapus">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </form>
                                <?php else: ?>
                                <a href="<?= $baseUrl ?>/labeling/create/<?= $row['pemeliharaan_id'] ?>"
                                   class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-primary text-white hover:bg-on-primary-fixed-variant transition-colors flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">add</span> Label
                                </a>
                                <?php endif; ?>
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
                <a href="<?= $baseUrl ?>/labeling<?= labelQ(['page' => $page - 1]) ?>"
                   class="px-3 py-1.5 text-sm rounded-lg text-primary hover:bg-primary-fixed transition-colors font-medium flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px]">chevron_left</span> Prev
                </a>
                <?php endif; ?>
                <?php for ($p = max(1,$page-2); $p <= min($totalPages,$page+2); $p++): ?>
                <a href="<?= $baseUrl ?>/labeling<?= labelQ(['page' => $p]) ?>"
                   class="w-8 h-8 flex items-center justify-center text-sm rounded-lg font-medium transition-colors <?= $p===$page?'bg-primary text-white':'text-on-surface-variant hover:bg-surface-container' ?>">
                    <?= $p ?>
                </a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                <a href="<?= $baseUrl ?>/labeling<?= labelQ(['page' => $page + 1]) ?>"
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
<script>
function confirmAutoLabel() {
    const overwrite = document.getElementById('overwriteCheck').checked;
    const msg = overwrite
        ? 'Seluruh <?= $total ?> data akan dilabeli ulang, menimpa label yang sudah ada.'
        : 'Label FMEA akan dihitung otomatis untuk seluruh data yang belum berlabel.';
    showDialog({
        title:       overwrite ? 'Timpa Semua Label' : 'Label Otomatis',
        message:     msg,
        type:        overwrite ? 'warning' : 'info',
        confirmText: 'Ya, Lanjutkan',
        onConfirm:   () => document.getElementById('autoLabelForm').submit(),
    });
}
</script>
</body>
</html>
