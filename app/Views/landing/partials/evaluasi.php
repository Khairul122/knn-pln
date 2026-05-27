<?php
/* ═══════════════════════════════════════════
   EVALUASI SECTION
   Variables inherited: $summary, $monthly, $penyulangCount, $tahun, $baseUrl
   ═══════════════════════════════════════════ */
$total   = (int)($summary['total_pemeliharaan'] ?? 0);
$labeled = (int)($summary['total_labeled']      ?? 0);
$rendah  = (int)($summary['rendah']             ?? 0);
$sedang  = (int)($summary['sedang']             ?? 0);
$tinggi  = (int)($summary['tinggi']             ?? 0);
$avgRpn  = round((float)($summary['avg_rpn']    ?? 0), 1);
$maxRpn  = (int)($summary['max_rpn']            ?? 0);

$hasData = $total > 0;
$pct     = fn(int $n) => $labeled > 0 ? round($n / $labeled * 100, 1) : 0;

// Monthly chart data (only months that have data)
$namaBulan   = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
$monthlyMap  = array_column($monthly, null, 'bulan');
$chartLabels = $chartR = $chartS = $chartT = [];
for ($b = 1; $b <= 12; $b++) {
    if (!isset($monthlyMap[$b])) continue;
    $chartLabels[] = $namaBulan[$b];
    $chartR[]      = (int)($monthlyMap[$b]['rendah'] ?? 0);
    $chartS[]      = (int)($monthlyMap[$b]['sedang'] ?? 0);
    $chartT[]      = (int)($monthlyMap[$b]['tinggi'] ?? 0);
}
?>
<section id="evaluasi" class="py-20 container mx-auto px-margin-desktop">

  <!-- Section header -->
  <div class="text-center mb-14 reveal">
    <p class="text-label-caps text-primary font-semibold uppercase tracking-widest mb-2">Data Tahun <?= $tahun ?></p>
    <h2 class="font-display-lg text-display-lg text-on-surface mb-4">Evaluasi Risiko Terpadu</h2>
    <div class="w-24 h-1 bg-primary mx-auto rounded-full"></div>
  </div>

  <!-- ── Row 1: Stat cards ── -->
  <div class="grid grid-cols-2 md:grid-cols-4 gap-5 mb-12 reveal">
    <?php foreach ([
      ['dataset',    'Total Data',       $total,          'text-primary',    'border-primary/40',       $hasData ? '' : 'Belum ada data'],
      ['label',      'Data Berlabel',    $labeled,        'text-secondary',  'border-secondary/40',     ''],
      ['cell_tower', 'Penyulang',        $penyulangCount, 'text-teal-700',   'border-teal-400/50',      ''],
      ['speed',      'Rata-rata RPN',    $avgRpn,         'text-amber-700',  'border-amber-400/50',     ''],
    ] as [$icon, $lbl, $val, $cls, $border, $empty]): ?>
    <div class="bg-surface-container-lowest rounded-2xl border-l-4 <?= $border ?> p-5 ambient-shadow">
      <div class="flex items-center gap-2 mb-2">
        <span class="material-symbols-outlined text-[18px] <?= $cls ?> opacity-60"><?= $icon ?></span>
        <p class="text-label-caps text-on-surface-variant uppercase tracking-wider text-[10px]"><?= $lbl ?></p>
      </div>
      <p class="text-3xl font-bold <?= $cls ?>"><?= $val !== 0 || !$empty ? $val : '—' ?></p>
      <?php if ($empty && $val === 0): ?>
      <p class="text-[11px] text-outline mt-1"><?= $empty ?></p>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- ── Row 2: Risk distribution + Monthly chart ── -->
  <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 mb-14">

    <!-- Distribution panel (2 col) -->
    <div class="lg:col-span-2 bg-surface-container-lowest rounded-2xl border border-outline-variant/30 p-6 ambient-shadow reveal">
      <h3 class="font-headline-md text-headline-md text-on-surface mb-1">Distribusi Risiko</h3>
      <p class="text-body-sm text-outline mb-6">
        <?= $labeled ?> data berlabel · Tahun <?= $tahun ?>
      </p>

      <?php if ($labeled > 0): ?>
      <div class="space-y-5">
        <?php foreach ([
          ['Rendah', $rendah, 'bg-green-500',  'text-green-700',  'bg-green-50'],
          ['Sedang', $sedang, 'bg-amber-500',  'text-amber-700',  'bg-amber-50'],
          ['Tinggi', $tinggi, 'bg-red-500',    'text-red-700',    'bg-red-50'],
        ] as [$lbl, $cnt, $bar, $txt, $bg]): ?>
        <div>
          <div class="flex justify-between items-center mb-1.5">
            <span class="text-body-sm font-semibold text-on-surface"><?= $lbl ?></span>
            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full <?= $bg ?> <?= $txt ?>">
              <?= $cnt ?> &nbsp;(<?= $pct($cnt) ?>%)
            </span>
          </div>
          <div class="w-full h-3 bg-surface-container-high rounded-full overflow-hidden">
            <div class="h-full <?= $bar ?> rounded-full transition-all duration-1000"
                 style="width: <?= $pct($cnt) ?>%"></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- RPN range -->
      <div class="mt-6 pt-5 border-t border-outline-variant/20 grid grid-cols-2 gap-3">
        <div class="text-center">
          <p class="text-[11px] text-outline uppercase tracking-wider">Max RPN</p>
          <p class="text-xl font-bold text-red-600 mt-0.5"><?= $maxRpn ?></p>
        </div>
        <div class="text-center">
          <p class="text-[11px] text-outline uppercase tracking-wider">Avg RPN</p>
          <p class="text-xl font-bold text-amber-600 mt-0.5"><?= $avgRpn ?></p>
        </div>
      </div>

      <?php else: ?>
      <div class="flex flex-col items-center justify-center py-12 text-center">
        <span class="material-symbols-outlined text-[48px] text-outline/40 mb-3">bar_chart</span>
        <p class="text-body-sm text-outline">Belum ada data berlabel untuk tahun <?= $tahun ?></p>
        <a href="<?= $baseUrl ?>/login"
           class="mt-4 text-xs font-semibold text-primary hover:underline">Mulai labeling →</a>
      </div>
      <?php endif; ?>
    </div>

    <!-- Monthly stacked bar chart (3 col) -->
    <div class="lg:col-span-3 bg-surface-container-lowest rounded-2xl border border-outline-variant/30 p-6 ambient-shadow reveal">
      <h3 class="font-headline-md text-headline-md text-on-surface mb-1">Distribusi Bulanan</h3>
      <p class="text-body-sm text-outline mb-4">Jumlah kasus per kategori risiko · Tahun <?= $tahun ?></p>

      <?php if (!empty($chartLabels)): ?>
      <div class="h-56">
        <canvas id="evalMonthlyChart"></canvas>
      </div>
      <?php else: ?>
      <div class="h-56 flex flex-col items-center justify-center text-center">
        <span class="material-symbols-outlined text-[48px] text-outline/40 mb-3">insert_chart</span>
        <p class="text-body-sm text-outline">Belum ada data bulanan</p>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php if (!empty($chartLabels)): ?>
<script>
(function() {
  const ctx = document.getElementById('evalMonthlyChart');
  if (!ctx) return;
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: <?= json_encode($chartLabels) ?>,
      datasets: [
        { label: 'Rendah', data: <?= json_encode($chartR) ?>, backgroundColor: 'rgba(34,197,94,0.75)',  borderRadius: 3 },
        { label: 'Sedang', data: <?= json_encode($chartS) ?>, backgroundColor: 'rgba(245,158,11,0.75)', borderRadius: 3 },
        { label: 'Tinggi', data: <?= json_encode($chartT) ?>, backgroundColor: 'rgba(239,68,68,0.75)',  borderRadius: 3 },
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: {
        legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
        tooltip: { mode: 'index', intersect: false }
      },
      scales: {
        x: { stacked: true, grid: { display: false }, ticks: { font: { size: 11 } } },
        y: { stacked: true, beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { font: { size: 11 }, stepSize: 1 } }
      }
    }
  });
})();
</script>
<?php endif; ?>
