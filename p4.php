<?php
// Add featured column + featured section to discover. DELETE AFTER RUN.
require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';
$pdo = db();

// 1. Add featured column
try {
    $pdo->exec("ALTER TABLE stores ADD COLUMN featured INTEGER NOT NULL DEFAULT 0");
    echo "featured column added\n";
} catch (Exception $e) {
    echo "featured column: " . $e->getMessage() . "\n";
}

// 2. Mark 2 demos as featured (example)
$pdo->exec("UPDATE stores SET featured=1 WHERE slug IN ('tandoor-nights', 'smash-bros')");
echo "marked featured\n";

@unlink(__FILE__);
