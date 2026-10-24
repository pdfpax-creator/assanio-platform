<?php
// Reset CORRECT super admin password. DELETE AFTER RUN.
require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';
$pdo = db();

$newPass = 'Assanio2026!Master';
$hash = password_hash($newPass, PASSWORD_BCRYPT);

// Correct email: mrmujtabaawan@gmail.com (double 'a')
$st = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = 'mrmujtabaawan@gmail.com' AND role = 'super'");
$st->execute([$hash]);

echo "Rows updated: " . $st->rowCount() . "\n";
echo "Super admin password reset done.\n";

@unlink(__FILE__);
