<?php
// Delete broken test stores. DELETE AFTER RUN.
require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';
$pdo = db();

foreach (['bbq-bazaar-test', 'downtown-slice-test'] as $slug) {
    $pdo->prepare("DELETE FROM orders WHERE store_id IN (SELECT id FROM stores WHERE slug=?)")->execute([$slug]);
    $pdo->prepare("DELETE FROM stores WHERE slug=?")->execute([$slug]);
    $dir = __DIR__ . "/stores/$slug";
    if (is_dir($dir)) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $f) { $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname()); }
        rmdir($dir);
        echo "$slug: deleted\n";
    }
}
@unlink(__FILE__);
echo "Done - now recreate via create-store.php in browser\n";
