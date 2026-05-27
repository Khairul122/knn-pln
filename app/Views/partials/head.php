<?php
/**
 * Shared <head> partial (Tailwind CDN + fonts + base styles).
 * Expects:
 *   $pageHeadTitle (string) — value for <title>
 *
 * After including, add page-specific <style> or <script> tags as needed.
 */
$pageHeadTitle ??= 'PLN GridRisk';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageHeadTitle) ?> — PLN GridRisk</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<script>
    tailwind.config = {
        darkMode: "class",
        theme: { extend: {
            colors: {
                "primary":"#006492","primary-container":"#00a2e9","primary-fixed":"#cae6ff",
                "on-primary":"#ffffff","on-primary-fixed-variant":"#004b6f",
                "secondary":"#1b60a2","secondary-container":"#7eb6fe",
                "on-secondary":"#ffffff","on-secondary-container":"#00477e",
                "tertiary":"#576065","tertiary-container":"#919aa0",
                "surface":"#f8f9ff","surface-bright":"#f8f9ff",
                "surface-container-lowest":"#ffffff","surface-container-low":"#eff4ff",
                "surface-container":"#e5eeff","surface-container-high":"#dce9ff",
                "surface-container-highest":"#d3e4fe","on-surface":"#0b1c30",
                "on-surface-variant":"#3e4850","outline":"#6e7882",
                "outline-variant":"#bec8d2","background":"#f8f9ff",
                "on-background":"#0b1c30","error":"#ba1a1a",
                "error-container":"#ffdad6","on-error":"#ffffff","on-error-container":"#93000a",
            },
            fontFamily: { sans: ["Inter"], "body-sm":["Inter"], "body-lg":["Inter"],
                "display-lg":["Inter"], "headline-md":["Inter"], "label-caps":["Inter"], "numeric-data":["Inter"] },
            fontSize: {
                "label-caps":   ["12px", { lineHeight:"16px", letterSpacing:"0.05em", fontWeight:"600" }],
                "display-lg":   ["32px", { lineHeight:"40px", letterSpacing:"-0.02em", fontWeight:"700" }],
                "body-lg":      ["16px", { lineHeight:"24px", fontWeight:"400" }],
                "headline-md":  ["20px", { lineHeight:"28px", letterSpacing:"-0.01em", fontWeight:"600" }],
                "body-sm":      ["14px", { lineHeight:"20px", fontWeight:"400" }],
                "numeric-data": ["24px", { lineHeight:"32px", letterSpacing:"-0.01em", fontWeight:"500" }],
            },
        }}
    }
</script>
<style>
    body { font-family: 'Inter', sans-serif; background-color: #f8f9ff; }
    .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    .custom-shadow { box-shadow: 0px 4px 20px rgba(0,0,0,0.04); }
    .hover-card { transition: all 0.25s cubic-bezier(0.4,0,0.2,1); }
    .hover-card:hover { box-shadow: 0px 10px 30px rgba(0,100,146,0.10); transform: translateY(-2px); }
    /* Toast */
    @keyframes toast-in  { from { opacity:0; transform:translateX(16px); } to { opacity:1; transform:translateX(0); } }
    @keyframes toast-out { from { opacity:1; transform:translateX(0); } to { opacity:0; transform:translateX(16px); } }
    .toast-enter { animation: toast-in  .3s cubic-bezier(.22,1,.36,1) forwards; }
    .toast-leave { animation: toast-out .25s ease forwards; }
    .progress-bar { animation: progress-shrink linear forwards; }
    @keyframes progress-shrink { from { width: 100%; } to { width: 0%; } }
    /* Dialog */
    @keyframes dlg-in  { from { opacity:0; transform:scale(.95) translateY(8px); } to { opacity:1; transform:scale(1) translateY(0); } }
    @keyframes dlg-out { from { opacity:1; transform:scale(1)  translateY(0); }    to { opacity:0; transform:scale(.95) translateY(8px); } }
    .dlg-enter { animation: dlg-in  .2s cubic-bezier(.22,1,.36,1) forwards; }
    .dlg-leave { animation: dlg-out .15s ease forwards; }
</style>
