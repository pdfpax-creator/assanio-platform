<?php
// One-time cleanup: remove test leads. DELETE AFTER RUN.
require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';

$pdo = db();
$deleted = $pdo->exec("DELETE FROM leads WHERE restaurant_name LIKE '%test%' OR restaurant_name LIKE '%Test%' OR contact_name LIKE '%test%' OR contact_name LIKE '%Test%' OR phone LIKE '%000000%'");
echo "Deleted $deleted test lead(s). DONE.\n";
@unlink(__FILE__);
