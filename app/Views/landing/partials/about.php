<!-- ═══════════════════════════════════════════
     ABOUT SECTION  (profile card + CTA banner)
     Variables inherited: $baseUrl
     ═══════════════════════════════════════════ -->
<section id="about" class="py-24 container mx-auto px-margin-desktop">
  <div class="max-w-4xl mx-auto bg-surface-container-lowest rounded-3xl p-12 border border-outline-variant/30
              flex flex-col md:flex-row gap-12 items-center reveal">

    <!-- Photo -->
    <div class="md:w-1/3 flex-shrink-0">
      <img alt="Engineers at work"
           class="rounded-2xl shadow-lg w-full aspect-square object-cover"
           src="https://lh3.googleusercontent.com/aida-public/AB6AXuD9DBzWrbV_SiIeqkwV7B0NxYzOLBLeLhJpdY4ky9ydPH3_yDW408JMKl4dgd8D0_wpFMD-XNSq5OVbXvNQ0wo78G3HaCAxMX2WnrpmyxbFMcd7jn-1xaUWwLYDqd17X5s2fbdt88iC7oqyK0bkncP-NaoDVr4bHFVl_3B93w2f3HX8lKpnWk6J5XRIVsffiBwM_CD2HbDYGgOFyfPNWyy3pPEfdoHYLO8dCEDO8R2ofpDf3WvwXO3-uzZ3OxBqX2T4e3YZYrA2Th0Z">
    </div>

    <!-- Text -->
    <div class="md:w-2/3">
      <h2 class="font-display-lg text-display-lg text-on-surface mb-6">Tentang PLN GridRisk</h2>
      <p class="font-body-lg text-body-lg text-on-surface-variant mb-6 leading-relaxed">
        PLN GridRisk adalah sistem analitik prediktif untuk modernisasi infrastruktur jaringan distribusi listrik.
        Dengan mengimplementasikan algoritma <strong>K-Nearest Neighbor (KNN)</strong>, sistem ini mengalihkan
        paradigma dari pemeliharaan reaktif menuju pemeliharaan prediktif yang cerdas.
      </p>
      <p class="font-body-lg text-body-lg text-on-surface-variant mb-8 leading-relaxed">
        Sistem mengolah data pemeliharaan penyulang — inspeksi tier&nbsp;1 &amp; tier&nbsp;2, pengukuran,
        pergantian FCO, penyeimbangan beban, dan lainnya — untuk memastikan setiap penyulang mendapat
        prioritas pemeliharaan yang tepat.
      </p>
      <div class="flex flex-wrap gap-6">
        <div class="flex items-center gap-3 text-primary">
          <span class="material-symbols-outlined text-[36px]">verified_user</span>
          <div>
            <p class="font-label-caps text-label-caps uppercase font-bold">Standard Keamanan</p>
            <p class="font-body-sm text-body-sm opacity-70">ISO 27001 &amp; SNI Power Quality</p>
          </div>
        </div>
        <div class="flex items-center gap-3 text-secondary">
          <span class="material-symbols-outlined text-[36px]">school</span>
          <div>
            <p class="font-label-caps text-label-caps uppercase font-bold">Riset Akademik</p>
            <p class="font-body-sm text-body-sm opacity-70">Berbasis metodologi FMEA &amp; KNN</p>
          </div>
        </div>
      </div>
    </div>

  </div>
</section>

<!-- CTA Banner (part of About context) -->
<section class="py-20 bg-primary">
  <div class="container mx-auto px-margin-desktop text-center reveal">
    <h2 class="font-display-lg text-display-lg text-white mb-4">Siap Memulai Analisis?</h2>
    <p class="font-body-lg text-body-lg text-blue-100 mb-8 max-w-xl mx-auto">
      Masuk ke dashboard untuk mengolah data pemeliharaan, melatih model KNN, dan menghasilkan laporan risiko yang komprehensif.
    </p>
    <a href="<?= $baseUrl ?>/login"
       class="inline-flex items-center gap-2 bg-white text-primary font-headline-md text-headline-md px-10 py-4 rounded-xl hover:scale-105 transition-transform shadow-lg">
      Masuk ke Dashboard
      <span class="material-symbols-outlined">login</span>
    </a>
  </div>
</section>
