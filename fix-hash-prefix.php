<?php
// Fix corrupted bcrypt hashes (add missing $2 prefix). DELETE AFTER RUN.
$stores = ['tandoor-nights','brew-and-bean','maison-olive','smash-bros','downtown-slice','br9-cafe'];
foreach ($stores as $slug) {
    $f = __DIR__ . "/stores/$slug/admin/config.php";
    if (!file_exists($f)) { echo "$slug: NOT FOUND\n"; continue; }
    $c = file_get_contents($f);
    // Fix: y$ -> $2y$ (missing $2 prefix)
    $fixed = str_replace("define('CH_ADMIN_HASH', 'y\$", "define('CH_ADMIN_HASH', '\$2y\$", $c);
    if ($fixed !== $c) {
        file_put_contents($f, $fixed);
        echo "$slug: prefix fixed\n";
    } else {
        echo "$slug: no fix needed or not found\n";
    }
}
echo "DONE.\n";
