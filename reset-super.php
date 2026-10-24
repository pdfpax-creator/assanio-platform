<?php
// Reset super admin password. DELETE AFTER RUN.
require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';
$pdo = db();

$newPass = 'Assanio2026!Master';
$hash = password_hash($newPass, PASSWORD_BCRYPT);

$st = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = 'mrmujtabawan@gmail.com' AND role = 'super'");
$st->execute([$hash]);

echo "Rows updated: " . $st->rowCount() . "\n";
echo "Super admin password reset done.\n";
echo "New password saved to ASSANIO-STORE-PASSWORDS.md (not shown here).\n";

@unlink(__FILE__);
