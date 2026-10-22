<?php
// Surgical flicker fix for store index.html files. DELETE AFTER RUN.
$stores = ['tandoor-nights','brew-and-bean','maison-olive','smash-bros','downtown-slice','br9-cafe'];

foreach ($stores as $slug) {
    $f = __DIR__ . "/stores/$slug/index.html";
    if (!file_exists($f)) { echo "$slug: NOT FOUND\n"; continue; }
    $html = file_get_contents($f);
    $orig = $html;

    // 1. Fix CSS: .rv should not hide by default
    $html = str_replace(
        '.rv{opacity:0;transition:opacity .7s ease-out,transform .7s ease-out;will-change:opacity,transform}',
        '.rv{transition:opacity .7s ease-out,transform .7s ease-out;will-change:opacity,transform}' . "\n" . '.rv-hide{opacity:0}',
        $html
    );
    // 2. Remove no-js from body
    $html = str_replace('<body class="no-js">', '<body>', $html);
    // 3. Replace no-js removal with rv-hide setup
    $html = str_replace(
        "document.body.classList.remove('no-js');",
        "document.querySelectorAll('.rv').forEach(function(el){ el.classList.add('rv-hide'); });",
        $html
    );
    // 4. Remove rv-hide when revealing
    $html = str_replace(
        "el.classList.add('rv-on')",
        "el.classList.add('rv-on');el.classList.remove('rv-hide')",
        $html
    );

    if ($html !== $orig) {
        file_put_contents($f, $html);
        echo "$slug: fixed\n";
    } else {
        echo "$slug: no changes (already fixed or pattern not found)\n";
    }
}
echo "DONE.\n";
