<?php
/**
 * Reusable top header partial.
 * Expects from parent scope:
 *   $baseUrl        (string)
 *   $pageTitle      (string)  — shown next to icon
 *   $pageIcon       (string)  — Material Symbol name, default 'dashboard'
 *   $backUrl        (string|null) — if set, shows back arrow linking here
 *   $headerActions  (string|null) — raw HTML for right-side action buttons
 */
$pageIcon      ??= 'dashboard';
$backUrl       ??= null;
$headerActions ??= null;
?>
<header class="h-16 fixed top-0 right-0 left-0 md:left-64 bg-surface-container-lowest flex items-center px-4 sm:px-6 z-30 shadow-sm border-b border-outline-variant/50 gap-3">

    <!-- Hamburger (mobile) -->
    <button onclick="openSidebar()"
            class="md:hidden p-2 -ml-1 rounded-lg text-on-surface-variant hover:bg-surface-container transition-colors flex-shrink-0">
        <span class="material-symbols-outlined">menu</span>
    </button>

    <!-- Back arrow -->
    <?php if ($backUrl): ?>
    <a href="<?= htmlspecialchars($backUrl) ?>"
       class="flex items-center gap-1 text-outline hover:text-primary transition-colors flex-shrink-0">
        <span class="material-symbols-outlined text-[20px]">arrow_back</span>
    </a>
    <?php endif; ?>

    <!-- Page title -->
    <div class="flex items-center gap-2 min-w-0">
        <span class="material-symbols-outlined text-primary text-[20px] flex-shrink-0"><?= $pageIcon ?></span>
        <h2 class="font-semibold text-on-surface text-sm sm:text-base truncate"><?= htmlspecialchars($pageTitle) ?></h2>
    </div>

    <!-- Right actions slot -->
    <?php if ($headerActions): ?>
    <div class="ml-auto flex items-center gap-2 sm:gap-3">
        <?= $headerActions ?>
    </div>
    <?php endif; ?>
</header>
