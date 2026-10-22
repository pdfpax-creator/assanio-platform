<?php
// Auto-generate monthly MLM commissions. Run via cron on 1st of each month.
// Cron: 0 9 1 * * php /home/u847179061/domains/pdfpax.com/public_html/app/cron-monthly-commissions.php
require __DIR__ . '/lib.php';

$pdo = db();
$month = date('Y-m');

try {
    $count = mlm_calculate_monthly($pdo, $month);
    $msg = date('Y-m-d H:i:s') . " - Generated $count commissions for $month\n";
} catch (Throwable $e) {
    $msg = date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n";
}

// Log it
file_put_contents(__DIR__ . '/data/cron-commissions.log', $msg, FILE_APPEND);
echo $msg;
