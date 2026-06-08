<!-- ═══════════════════════════════════════════
     HERO SECTION
     Variables inherited from parent scope: $baseUrl, $summary, $penyulangCount, $latestModel
     ═══════════════════════════════════════════ -->
<?php
$heroTotalData = number_format((int)($summary['total_pemeliharaan'] ?? 0));
$heroAkurasi   = !empty($latestModel) ? round(((float)$latestModel['accuracy']) * 100, 1) . '%' : '-';
?>
<section id="hero" class="relative h-[600px] flex items-center overflow-hidden">
  <div class="absolute inset-0 z-0">
    <img alt="Grid Infrastructure"
         class="w-full h-full object-cover"
         src="https://lh3.googleusercontent.com/aida-public/AB6AXuAKrLWaaUqNYIcXwZJXhFz8O0RxxpZHYVOhhCoBLCLLWMTC9uF-4WHB6yNkrEOSbYBqDYkxB5vpvmwxnOi9WgttE_EWRO5Qhsz6KbbiQkNExTzsvBCKMVfJuboPs6b0b8UjdMqt3D1Iu7zYlAjeUP1fS9h_eaypb27JWfBN4Iu21Ck1dkni8bU6xkGqWgpE5PWZxp00YnddVZsj_4zSpFK_czG17pRzH9apZBEf-wC_FIyzjZcByS_fKuJya9v9_0AuZ2OuSwtICkM7">
    <div class="absolute inset-0 hero-gradient"></div>
  </div>

  <div class="container mx-auto px-margin-desktop relative z-10">
    <div class="max-w-2xl">
      <span class="inline-block bg-primary-fixed text-on-primary-fixed-variant font-label-caps text-label-caps px-3 py-1 rounded-full mb-6">
        INTELIJEN INFRASTRUKTUR
      </span>
      <h2 class="font-display-lg text-display-lg text-white mb-6 leading-tight">
        Transformasi Pemeliharaan Jaringan dengan Kecerdasan Prediktif
      </h2>
      <p class="font-body-lg text-body-lg text-blue-50 mb-8 opacity-90">
        Mengoptimalkan keandalan energi nasional melalui algoritma K-Nearest Neighbor yang mendeteksi anomali dan risiko interferensi secara presisi sebelum kegagalan terjadi.
      </p>
      <div class="flex flex-wrap gap-4">
        <a href="<?= $baseUrl ?>/login"
           class="bg-primary-container text-on-primary-container px-8 py-4 rounded-xl font-headline-md text-headline-md flex items-center gap-2 hover:scale-105 transition-transform ambient-shadow">
          Mulai Analisis <span class="material-symbols-outlined">trending_up</span>
        </a>
        <a href="#prediksi"
           class="bg-white/10 backdrop-blur-md text-white border border-white/20 px-8 py-4 rounded-xl font-headline-md text-headline-md hover:bg-white/20 transition-all">
          Lihat Teknologi
        </a>
      </div>
    </div>
  </div>
</section>

<!-- Stats Strip -->
<div class="bg-primary text-white py-8">
  <div class="container mx-auto px-margin-desktop grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
    <?php foreach ([
      [(string)($penyulangCount ?? 0), 'Penyulang Terpantau'],
      [$heroTotalData,                 'Data Pemeliharaan'],
      [$heroAkurasi,                   'Akurasi KNN'],
      ['< 2ms',                        'Waktu Prediksi'],
    ] as [$val, $lbl]): ?>
    <div class="reveal">
      <p class="text-3xl font-bold"><?= $val ?></p>
      <p class="text-sm text-blue-200 mt-1"><?= $lbl ?></p>
    </div>
    <?php endforeach; ?>
  </div>
</div>
