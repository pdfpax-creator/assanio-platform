<?php
// FIX MAISON OLIVE MONOGRAM - upload to app/, run once, DELETE after
$file = __DIR__ . '/stores/maison-olive/index.html';
if (!file_exists($file)) { die("Not found"); }

$html = file_get_contents($file);
// Replace hardcoded N with dynamic monogram
$html = str_replace(
    '<text x="12" y="17.5" text-anchor="middle" font-family="Georgia,serif" font-size="15" fill="#ecc246">N</text>',
    '<text x="12" y="17.5" text-anchor="middle" font-family="Georgia,serif" font-size="15" fill="#ecc246" class="mono-letter">M</text>',
    $html
);
// Add JS to make it dynamic
$html = str_replace(
    '$("logoText").textContent=(c.header.logoText||S.name||"").toUpperCase();',
    '$("logoText").textContent=(c.header.logoText||S.name||"").toUpperCase();var monoLetter=((c.header.logoText||S.name||"M").charAt(0)||"M").toUpperCase();document.querySelectorAll(".mono-letter").forEach(function(el){el.textContent=monoLetter;});',
    $html
);
file_put_contents($file, $html);
echo "✅ Maison Olive monogram fixed to M (dynamic)";
