<?php
// Diagnose super admin account. DELETE AFTER RUN.
require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';
$pdo = db();

$st = $pdo->query("SELECT id, name, email, role, approved FROM users ORDER BY id");
foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $u) {
    echo "ID:{$u['id']} | {$u['name']} | {$u['email']} | role:{$u['role']} | approved:{$u['approved']}\n";
}

@unlink(__FILE__);
