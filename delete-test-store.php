<?php
// Delete test store. DELETE AFTER RUN.
require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';

$pdo = db();
$slug = 'test-delete-me';

// Get store
$st = $pdo->prepare("SELECT id, name FROM stores WHERE slug = ?");
$st->execute([$slug]);
$store = $st->fetch();
if (!$store) { echo "Store not found: $slug\n"; exit; }

$id = (int)$store['id'];
echo "Deleting: {$store['name']} (ID $id)\n";

// Delete orders
$pdo->prepare("DELETE FROM orders WHERE store_id = ?")->execute([$id]);
echo "Orders deleted\n";

// Delete store record
$pdo->prepare("DELETE FROM stores WHERE id = ?")->execute([$id]);
echo "Store record deleted\n";

// Delete files
$dir = __DIR__ . "/stores/$slug";
if (is_dir($dir)) {
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($dir);
    echo "Files deleted\n";
}
echo "DONE.\n";
@unlink(__FILE__);
