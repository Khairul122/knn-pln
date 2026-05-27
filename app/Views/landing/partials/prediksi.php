<?php
/* ═══════════════════════════════════════════
   PREDIKSI SECTION  (KNN model real stats)
   Variables inherited: $latestModel, $totalModels, $monthly, $tahun, $baseUrl
   ═══════════════════════════════════════════ */
$hasModel = !empty($latestModel);

$fmtPct = fn($v) => $v !== null ? round((float)$v * 100, 1) . '%' : '—';

// Monthly trend for right-side chart
$namaBulan  = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
$monthlyMap = array_column($monthly ?? [], null, 'bulan');
$chartLabels = $chartR = $chartS = $chartT = [];
for ($b = 1; $b <= 12; $b++) {
    if (!isset($monthlyMap[$b])) continue;
    $chartLabels[] = $namaBulan[$b];
    $chartR[]      = (int)($monthlyMap[$b]['rendah'] ?? 0);
    $chartS[]      = (int)($monthlyMap[$b]['sedang'] ?? 0);
    $chartT[]      = (int)($monthlyMap[$b]['tinggi'] ?? 0);
}
?>
<section id="prediksi" class="py-20 bg-surface-container-low/50">
  <div class="container mx-auto px-margin-desktop">
    <div class="flex flex-col md:flex-row gap-12 items-start">

      <!-- ── Left: KNN model stats ── -->
      <div class="md:w-1/2 reveal">
        <span class="text-primary font-label-caps text-label-caps tracking-widest uppercase mb-4 block">
          Machine Learning Core
        </span>
        <h2 class="font-display-lg text-display-lg text-on-surface mb-4">Prediksi KNN yang Akurat</h2>
        <p class="font-body-lg text-body-lg text-on-surface-variant mb-8">
          Algoritma K-Nearest Neighbor (KNN) menganalisis data pemeliharaan historis untuk memprediksi
          level risiko setiap penyulang. Sistem dievaluasi dengan split stratifikasi train/test untuk
          memastikan akurasi yang dapat dipercaya.
        </p>

        <?php if ($hasModel): ?>
        <!-- ── Metric cards from latest model ── -->
        <div class="grid grid-cols-2 gap-4 mb-8">
          <?php foreach ([
            ['Akurasi',   $fmtPct($latestModel['accuracy']),        'text-primary',   'border-primary/30',   'verified'],
            ['F1-Score',  $fmtPct($latestModel['f1_score']),        'text-secondary', 'border-secondary/30', 'analytics'],
            ['Presisi',   $fmtPct($latestModel['precision_score']), 'text-teal-700',  'border-teal-400/40',  'precision_manufacturing'],
            ['Recall',    $fmtPct($latestModel['recall_score']),    'text-amber-700', 'border-amber-400/40', 'track_changes'],
          ] as [$lbl, $val, $cls, $border, $icon]): ?>
          <div class="bg-white rounded-2xl border <?= $border ?> p-5 shadow-sm">
            <div class="flex items-center gap-2 mb-2">
              <span class="material-symbols-outlined text-[16px] <?= $cls ?> opacity-60"><?= $icon ?></span>
              <span class="text-label-caps text-on-surface-variant uppercase text-[10px]"><?= $lbl ?></span>
            </div>
            <span class="text-[40px] font-bold <?= $cls ?> block leading-none"><?= $val ?></span>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Model info strip -->
        <div class="bg-white rounded-2xl border border-outline-variant/30 px-5 py-4 shadow-sm mb-8">
          <p class="text-[10px] text-outline uppercase tracking-wider font-semibold mb-3">Detail Model Terbaru</p>
          <div class="grid grid-cols-3 gap-3 text-center">
            <?php foreach ([
              ['K Tetangga',    $latestModel['k_value']],
              ['Data Train',    number_format($latestModel['train_count'])],
              ['Data Test',     number_format($latestModel['test_count'])],
            ] as [$lbl, $val]): ?>
            <div>
              <p class="text-lg font-bold text-on-surface"><?= $val ?></p>
              <p class="text-[10px] text-outline"><?= $lbl ?></p>
            </div>
            <?php endforeach; ?>
          </div>
          <div class="mt-3 pt-3 border-t border-outline-variant/20 flex flex-wrap gap-2">
            <span class="text-[10px] bg-primary/10 text-primary px-2 py-0.5 rounded-full font-semibold">
              <?= ucfirst($latestModel['distance_metric'] ?? 'euclidean') ?>
            </span>
            <?php foreach (explode(',', $latestModel['feature_columns'] ?? '') as $feat): ?>
            <span class="text-[10px] bg-surface-container text-on-surface-variant px-2 py-0.5 rounded-full">
              <?= trim($feat) ?>
            </span>
            <?php endforeach; ?>
            <?php if ($totalModels > 1): ?>
            <span class="text-[10px] bg-secondary/10 text-secondary px-2 py-0.5 rounded-full font-semibold ml-auto">
              <?= $totalModels ?> model tersimpan
            </span>
            <?php endif; ?>
          </div>
        </div>

        <?php else: ?>
        <!-- No model yet — placeholder cards -->
        <div class="grid grid-cols-2 gap-4 mb-8">
          <?php foreach (['Akurasi','F1-Score','Presisi','Recall'] as $lbl): ?>
          <div class="bg-white rounded-2xl border border-outline-variant/30 p-5 shadow-sm">
            <p class="text-label-caps text-on-surface-variant uppercase text-[10px] mb-2"><?= $lbl ?></p>
            <span class="text-[40px] font-bold text-outline/30 block leading-none">—</span>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="bg-amber-50 border border-amber-200 rounded-xl px-5 py-4 mb-8 flex items-start gap-3">
          <span class="material-symbols-outlined text-amber-600 flex-shrink-0 mt-0.5">info</span>
          <div>
            <p class="text-body-sm font-semibold text-amber-800">Model belum dilatih</p>
            <p class="text-[13px] text-amber-700 mt-0.5">
              Login dan latih model KNN untuk melihat metrik performa di sini.
            </p>
            <a href="<?= $baseUrl ?>/login"
               class="inline-flex items-center gap-1 mt-2 text-xs font-bold text-amber-800 hover:underline">
              Latih sekarang <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
            </a>
          </div>
        </div>
        <?php endif; ?>

        <!-- Workflow steps -->
        <ol class="space-y-3">
          <?php foreach ([
            'Input data pemeliharaan & FMEA labeling',
            'Split stratifikasi train / test',
            'Training KNN dengan konfigurasi K & fitur',
            'Evaluasi confusion matrix & metrik',
            'Prediksi batch seluruh jaringan',
          ] as $i => $step): ?>
          <li class="flex items-start gap-3">
            <span class="w-6 h-6 rounded-full bg-primary text-white text-xs flex items-center justify-center font-bold flex-shrink-0 mt-0.5">
              <?= $i + 1 ?>
            </span>
            <span class="text-body-sm text-on-surface-variant"><?= $step ?></span>
          </li>
          <?php endforeach; ?>
        </ol>
      </div>

      <!-- ── Right: live risk trend chart ── -->
      <div class="md:w-1/2 w-full reveal">
        <div class="glass-card p-8 rounded-3xl relative overflow-hidden elevated-shadow">
          <div class="flex justify-between items-center mb-2">
            <h4 class="font-headline-md text-headline-md">Tren Risiko per Bulan</h4>
            <span class="bg-primary-container text-on-primary-container text-body-sm px-3 py-1 rounded-full text-[11px] font-semibold">
              <?= $tahun ?>
            </span>
          </div>
          <p class="text-[12px] text-outline mb-6">Distribusi label risiko FMEA · Tahun <?= $tahun ?></p>

          <?php if (!empty($chartLabels)): ?>
          <!-- Real chart -->
          <div class="h-64">
            <canvas id="predMonthlyChart"></canvas>
          </div>
          <!-- Legend -->
          <div class="mt-6 grid grid-cols-3 gap-3">
            <?php foreach ([
              ['bg-green-500', 'Rendah'],
              ['bg-amber-500', 'Sedang'],
              ['bg-red-500',   'Tinggi'],
            ] as [$dot, $lbl]): ?>
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 <?= $dot ?> rounded-full flex-shrink-0"></div>
              <span class="text-body-sm text-on-surface-variant"><?= $lbl ?></span>
            </div>
            <?php endforeach; ?>
          </div>

          <?php else: ?>
          <!-- Animated placeholder bars (no real data yet) -->
          <div class="h-64 flex items-end gap-3 px-4" id="mock-bars">
            <?php foreach ([
              ['bg-secondary-container', 40], ['bg-primary-container', 75],
              ['bg-secondary-container', 30], ['bg-primary',           88],
              ['bg-secondary-container', 45], ['bg-primary-container', 82],
              ['bg-secondary-container', 55], ['bg-primary',           70],
            ] as [$color, $pct]): ?>
            <div class="w-full <?= $color ?> rounded-t-lg transition-all duration-700"
                 style="height: <?= $pct ?>%"></div>
            <?php endforeach; ?>
          </div>
          <div class="mt-6 grid grid-cols-3 gap-3">
            <?php foreach ([
              ['bg-primary',           'Tinggi'],
              ['bg-primary-container', 'Sedang'],
              ['bg-secondary-container','Rendah'],
            ] as [$dot, $lbl]): ?>
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 <?= $dot ?> rounded-full"></div>
              <span class="text-body-sm text-on-surface-variant"><?= $lbl ?></span>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

        </div>
      </div>

    </div>
  </div>
</section>

<?php if (!empty($chartLabels)): ?>
<script>
(function() {
  const ctx = document.getElementById('predMonthlyChart');
  if (!ctx) return;
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: <?= json_encode($chartLabels) ?>,
      datasets: [
        { label: 'Rendah', data: <?= json_encode($chartR) ?>, backgroundColor: 'rgba(34,197,94,0.8)',  borderRadius: 4 },
        { label: 'Sedang', data: <?= json_encode($chartS) ?>, backgroundColor: 'rgba(245,158,11,0.8)', borderRadius: 4 },
        { label: 'Tinggi', data: <?= json_encode($chartT) ?>, backgroundColor: 'rgba(239,68,68,0.8)',  borderRadius: 4 },
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: { mode: 'index', intersect: false }
      },
      scales: {
        x: { stacked: true, grid: { display: false }, ticks: { color: '#6e7882', font: { size: 11 } } },
        y: { stacked: true, beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { color: '#6e7882', font: { size: 11 }, stepSize: 1 } }
      }
    }
  });
})();
</script>
<?php else: ?>
<script>
const mockBars = document.querySelectorAll('#mock-bars > div');
if (mockBars.length) {
  setInterval(() => {
    mockBars.forEach(b => { b.style.height = (25 + Math.random() * 70) + '%'; });
  }, 2000);
}
</script>
<?php endif; ?>
