<?php
// Copy template index.html to stores (loader fix). Content is in content.json so safe. DELETE AFTER RUN.
$stores = [
    'tandoor-nights' => 'desi-bbq',
    'brew-and-bean' => 'cafe',
    'maison-olive' => 'fine-dining',
    'smash-bros' => 'pizza-fastfood',
    'downtown-slice' => 'pizza-fastfood',
    'br9-cafe' => 'pizza-fastfood',
];
foreach ($stores as $slug => $tpl) {
    $src = __DIR__ . "/templates/$tpl/index.html";
    $dst = __DIR__ . "/stores/$slug/index.html";
    if (copy($src, $dst)) {
        echo "$slug: updated\n";
    } else {
        echo "$slug: FAILED\n";
    }
}
echo "DONE.\n";
