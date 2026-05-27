<!-- ═══════════════════════════════════════════
     FOOTER
     Variables inherited: $baseUrl
     ═══════════════════════════════════════════ -->
<footer class="bg-surface-container-low border-t border-outline-variant/20 pt-16 pb-8">
  <div class="container mx-auto px-margin-desktop">

    <div class="grid grid-cols-1 md:grid-cols-3 gap-12 mb-12">

      <!-- Brand -->
      <div class="col-span-1">
        <div class="flex items-center gap-3 mb-6">
          <div class="w-8 h-8 bg-primary-container rounded flex items-center justify-center text-on-primary-container">
            <span class="material-symbols-outlined text-sm" style="font-variation-settings:'FILL' 1;">bolt</span>
          </div>
          <h5 class="font-headline-md text-headline-md font-bold text-primary">PLN GridRisk</h5>
        </div>
        <p class="text-body-sm text-on-surface-variant leading-relaxed">
          Pusat analisis risiko ketenagalistrikan nasional menggunakan kecerdasan buatan terapan.
        </p>
      </div>

      <!-- Navigasi Utama -->
      <div>
        <h6 class="font-label-caps text-label-caps uppercase mb-6 text-on-surface">Navigasi Utama</h6>
        <ul class="space-y-4">
          <li><a class="text-body-sm text-on-surface-variant hover:text-primary transition-colors" href="<?= $baseUrl ?>/dashboard">Dashboard</a></li>
          <li><a class="text-body-sm text-on-surface-variant hover:text-primary transition-colors" href="<?= $baseUrl ?>/pemeliharaan">Data Pemeliharaan</a></li>
          <li><a class="text-body-sm text-on-surface-variant hover:text-primary transition-colors" href="<?= $baseUrl ?>/knn/train">Prediksi KNN</a></li>
          <li><a class="text-body-sm text-on-surface-variant hover:text-primary transition-colors" href="<?= $baseUrl ?>/laporan">Laporan Risiko</a></li>
        </ul>
      </div>

      <!-- Informasi -->
      <div>
        <h6 class="font-label-caps text-label-caps uppercase mb-6 text-on-surface">Informasi</h6>
        <ul class="space-y-4">
          <li><a class="text-body-sm text-on-surface-variant hover:text-primary transition-colors" href="#hero">Hero</a></li>
          <li><a class="text-body-sm text-on-surface-variant hover:text-primary transition-colors" href="#evaluasi">Evaluasi</a></li>
          <li><a class="text-body-sm text-on-surface-variant hover:text-primary transition-colors" href="#prediksi">Prediksi</a></li>
          <li><a class="text-body-sm text-on-surface-variant hover:text-primary transition-colors" href="#about">About</a></li>
        </ul>
      </div>

    </div>

    <div class="pt-8 border-t border-outline-variant/20 flex flex-col md:flex-row justify-between items-center gap-4">
      <p class="text-body-sm text-on-surface-variant opacity-60">
        &copy; <?= date('Y') ?> PT PLN (Persero). Seluruh Hak Cipta Dilindungi.
      </p>
      <div class="flex gap-6">
        <a class="text-on-surface-variant hover:text-primary transition-colors" href="#">
          <span class="material-symbols-outlined">share</span>
        </a>
        <a class="text-on-surface-variant hover:text-primary transition-colors" href="#">
          <span class="material-symbols-outlined">public</span>
        </a>
      </div>
    </div>

  </div>
</footer>
