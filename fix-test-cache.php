<?php
// Add cache buster to test stores. DELETE AFTER RUN.
foreach (['bbq-bazaar-test', 'downtown-slice-test'] as $slug) {
    $f = __DIR__ . "/stores/$slug/index.html";
    if (!file_exists($f)) { echo "$slug: not found\n"; continue; }
    $h = file_get_contents($f);
    $h = str_replace('fetch("content.json")', 'fetch("content.json?v=test123")', $h);
    file_put_contents($f, $h);
    echo "$slug: cache buster added\n";
}
@unlink(__FILE__);
echo "Done\n";
