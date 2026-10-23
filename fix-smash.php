<?php
// Fresh sync smash-bros from template. DELETE AFTER RUN.
$tpl = __DIR__ . '/templates/pizza-fastfood';
$store = __DIR__ . '/stores/smash-bros';

// Copy all template files (except admin which has store-specific config)
$files = ['index.html', 'content.json', 'palettes.json'];
foreach ($files as $f) {
    if (file_exists("$tpl/$f")) {
        copy("$tpl/$f", "$store/$f");
        echo "$f: synced\n";
    }
}

// Copy images
if (is_dir("$tpl/images")) {
    foreach (glob("$tpl/images/*") as $img) {
        if (is_file($img)) copy($img, "$store/images/" . basename($img));
    }
    echo "images: synced\n";
}

@unlink(__FILE__);
echo "Done\n";
