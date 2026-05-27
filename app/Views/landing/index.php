<?php
$baseUrl = BASE_URL;
$LANDING = APP_PATH . '/Views/landing/partials/';
?>
<!DOCTYPE html>
<html class="light" lang="id">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>PLN GridRisk - Transformasi Pemeliharaan Jaringan</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <script id="tailwind-config">
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            "surface-container": "#e5eeff", "on-error-container": "#93000a",
            "on-secondary-container": "#00477e", "tertiary-fixed-dim": "#bfc8ce",
            "on-surface": "#0b1c30", "error-container": "#ffdad6",
            "secondary": "#1b60a2", "on-tertiary-fixed-variant": "#3f484d",
            "on-tertiary-fixed": "#141d21", "surface-container-high": "#dce9ff",
            "surface-bright": "#f8f9ff", "primary-fixed": "#cae6ff",
            "on-secondary-fixed": "#001c38", "surface-tint": "#006492",
            "inverse-on-surface": "#eaf1ff", "tertiary-fixed": "#dbe4ea",
            "primary-container": "#00a2e9", "primary-fixed-dim": "#8cceff",
            "on-secondary": "#ffffff", "on-background": "#0b1c30",
            "surface-container-lowest": "#ffffff", "inverse-surface": "#213145",
            "on-primary": "#ffffff", "on-error": "#ffffff", "outline": "#6e7882",
            "on-primary-container": "#00344f", "secondary-fixed": "#d3e4ff",
            "inverse-primary": "#8cceff", "surface-container-highest": "#d3e4fe",
            "surface-variant": "#d3e4fe", "outline-variant": "#bec8d2",
            "background": "#f8f9ff", "on-secondary-fixed-variant": "#004881",
            "on-primary-fixed-variant": "#004b6f", "surface": "#f8f9ff",
            "on-surface-variant": "#3e4850", "tertiary-container": "#919aa0",
            "secondary-container": "#7eb6fe", "error": "#ba1a1a",
            "surface-dim": "#cbdbf5", "surface-container-low": "#eff4ff",
            "tertiary": "#576065", "on-tertiary": "#ffffff",
            "on-primary-fixed": "#001e2f", "secondary-fixed-dim": "#a2c9ff",
            "on-tertiary-container": "#293237", "primary": "#006492"
          },
          borderRadius: { DEFAULT: "0.25rem", lg: "0.5rem", xl: "0.75rem", full: "9999px" },
          spacing: {
            base: "8px", "margin-mobile": "16px", "margin-desktop": "32px",
            gutter: "24px", "container-max": "1440px"
          },
          fontFamily: {
            "headline-md": ["Inter"], "numeric-data": ["Inter"], "label-caps": ["Inter"],
            "body-sm": ["Inter"], "display-lg-mobile": ["Inter"], "display-lg": ["Inter"],
            "body-lg": ["Inter"]
          },
          fontSize: {
            "headline-md":      ["20px", { lineHeight: "28px", letterSpacing: "-0.01em", fontWeight: "600" }],
            "numeric-data":     ["24px", { lineHeight: "32px", letterSpacing: "-0.01em", fontWeight: "500" }],
            "label-caps":       ["12px", { lineHeight: "16px", letterSpacing: "0.05em",  fontWeight: "600" }],
            "body-sm":          ["14px", { lineHeight: "20px", fontWeight: "400" }],
            "display-lg-mobile":["24px", { lineHeight: "32px", letterSpacing: "-0.02em", fontWeight: "700" }],
            "display-lg":       ["32px", { lineHeight: "40px", letterSpacing: "-0.02em", fontWeight: "700" }],
            "body-lg":          ["16px", { lineHeight: "24px", fontWeight: "400" }]
          }
        }
      }
    }
  </script>
  <style>
    body { font-family: 'Inter', sans-serif; }
    .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    .glass-card { background: rgba(255,255,255,0.8); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.3); }
    .hero-gradient { background: linear-gradient(135deg, rgba(0,100,146,0.9) 0%, rgba(0,162,233,0.7) 100%); }
    .ambient-shadow  { box-shadow: 0px 4px 20px rgba(0,0,0,0.03); }
    .elevated-shadow:hover { box-shadow: 0px 10px 30px rgba(0,162,233,0.08); }
    .reveal { opacity: 0; transform: translateY(24px); transition: opacity .6s ease, transform .6s ease; }
    .reveal.visible { opacity: 1; transform: none; }
    #mobile-menu { display: none; }
    #mobile-menu.open { display: flex; }
  </style>
</head>
<body class="bg-background text-on-background">

<!-- ── NAVBAR ── -->
<header class="fixed top-0 left-0 right-0 h-20 bg-surface/80 backdrop-blur-md z-50 border-b border-outline-variant/10">
  <div class="container mx-auto px-margin-desktop h-full flex items-center justify-between">

    <div class="flex items-center gap-3">
      <div class="w-10 h-10 bg-primary-container rounded-lg flex items-center justify-center text-on-primary-container">
        <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;">bolt</span>
      </div>
      <h1 class="font-headline-md text-headline-md font-bold text-primary">PLN GridRisk</h1>
    </div>

    <nav class="hidden md:flex items-center gap-8">
      <a href="#hero" class="text-body-sm font-semibold text-on-surface hover:text-primary transition-colors">Hero</a>
      <a href="#evaluasi" class="text-body-sm font-semibold text-on-surface hover:text-primary transition-colors">Evaluasi</a>
      <a href="#prediksi"  class="text-body-sm font-semibold text-on-surface hover:text-primary transition-colors">Prediksi</a>
      <a href="#about"   class="text-body-sm font-semibold text-on-surface hover:text-primary transition-colors">About</a>
    </nav>

    <div class="flex items-center gap-4">
      <a href="<?= $baseUrl ?>/login"
         class="text-body-sm font-semibold text-on-surface hover:text-primary transition-colors px-4 py-2">Masuk</a>
      <a href="<?= $baseUrl ?>/login"
         class="bg-primary text-white text-body-sm font-semibold px-6 py-2.5 rounded-full hover:bg-primary/90 transition-all shadow-sm">
        Mulai Sekarang
      </a>
      <button class="md:hidden p-2 rounded-lg text-on-surface hover:bg-surface-container transition-colors"
              onclick="toggleMobileNav()">
        <span class="material-symbols-outlined">menu</span>
      </button>
    </div>
  </div>

  <!-- Mobile dropdown -->
  <div id="mobile-menu"
       class="md:hidden absolute top-20 left-0 right-0 bg-surface border-b border-outline-variant/20 shadow-lg flex-col px-6 py-4 gap-4">
    <a href="#hero" class="text-body-sm font-semibold text-on-surface py-2" onclick="toggleMobileNav()">Hero</a>
    <a href="#evaluasi" class="text-body-sm font-semibold text-on-surface py-2" onclick="toggleMobileNav()">Evaluasi</a>
    <a href="#prediksi"  class="text-body-sm font-semibold text-on-surface py-2" onclick="toggleMobileNav()">Prediksi</a>
    <a href="#about"   class="text-body-sm font-semibold text-on-surface py-2" onclick="toggleMobileNav()">About</a>
    <hr class="border-outline-variant/30">
    <a href="<?= $baseUrl ?>/login"
       class="bg-primary text-white text-center font-semibold px-6 py-3 rounded-xl block">Masuk ke Dashboard</a>
  </div>
</header>

<main class="min-h-screen pt-20">

  <?php require $LANDING . 'hero.php';     ?>
  <?php require $LANDING . 'evaluasi.php'; ?>
  <?php require $LANDING . 'prediksi.php'; ?>
  <?php require $LANDING . 'about.php';    ?>
  <?php require $LANDING . 'footer.php';   ?>

</main>

<script>
  function toggleMobileNav() {
    document.getElementById('mobile-menu').classList.toggle('open');
  }

  // Smooth scroll
  document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
      const target = document.querySelector(a.getAttribute('href'));
      if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
    });
  });

  // Scroll reveal
  const revealObserver = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
  }, { threshold: 0.12 });
  document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));

  // 3D tilt on glass card
  const bentoCard = document.querySelector('.glass-card');
  if (bentoCard) {
    bentoCard.addEventListener('mousemove', e => {
      const { left, top, width, height } = bentoCard.getBoundingClientRect();
      const x = (e.clientX - left) / width, y = (e.clientY - top) / height;
      bentoCard.style.transform = `perspective(1000px) rotateY(${(x-.5)*5}deg) rotateX(${(y-.5)*-5}deg)`;
    });
    bentoCard.addEventListener('mouseleave', () => bentoCard.style.transform = 'none');
  }

  // Mock bars animation is handled inline in prediksi.php partial
</script>
</body>
</html>
