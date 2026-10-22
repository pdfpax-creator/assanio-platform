<?php
// FIX MAISON OLIVE MONOGRAM - run once then DELETE
$file = __DIR__ . '/stores/maison-olive/index.html';
if (!file_exists($file)) { die("Not found"); }
$html = file_get_contents($file);
$count = 0;
$html = str_replace(
    '<text x="12" y="17.5" text-anchor="middle" font-family="Georgia,serif" font-size="15" fill="#ecc246">N</text>',
    '<text x="12" y="17.5" text-anchor="middle" font-family="Georgia,serif" font-size="15" fill="#ecc246" class="mono-letter">M</text>',
    $html, $count
);
if ($count == 0) {
    // Already has class, just ensure M
    $html = str_replace('class="mono-letter">N<', 'class="mono-letter">M<', $html, $count);
}
$html = str_replace(
    '$("logoText").textContent=(c.header.logoText||S.name||"").toUpperCase();',
    '$("logoText").textContent=(c.header.logoText||S.name||"").toUpperCase();try{var ml=((c.header.logoText||S.name||"M").charAt(0)||"M").toUpperCase();document.querySelectorAll(".mono-letter").forEach(function(e){e.textContent=ml;});}catch(_){}',
    $html
);
file_put_contents($file, $html);
echo "✅ Fixed ($count replacements). DELETE THIS FILE NOW.";
// Self-delete
@unlink(__FILE__);
