<?php
// Sync template fixes to stores + create 2 real test stores. DELETE AFTER RUN.
require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';

// 1. Sync fixed index.html to existing desi-bbq and pizza stores
$stores = [
    'tandoor-nights' => 'desi-bbq',
    'downtown-slice' => 'pizza-fastfood',
    'br9-cafe' => 'pizza-fastfood',
    'smash-bros' => 'pizza-fastfood',
    'brew-and-bean' => 'cafe',
    'maison-olive' => 'fine-dining',
];
foreach ($stores as $slug => $tpl) {
    $src = __DIR__ . "/templates/$tpl/index.html";
    $dst = __DIR__ . "/stores/$slug/index.html";
    if (file_exists($src) && file_exists($dst)) {
        copy($src, $dst);
        echo "$slug: synced\n";
    }
}

echo "\nDone. Now create 2 stores manually via create-store.php:\n";
echo "1. BBQ BAZAAR (desi-bbq) - 29-C Plaza, Phase 4 Civic Center, Bahria Town\n";
echo "2. Downtown Slice Pizza (pizza-fastfood) - Plot 49, Phase 4 Civic Centre\n";
@unlink(__FILE__);
