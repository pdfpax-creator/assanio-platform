#!/usr/bin/env php
<?php
// Promote an existing registered user to platform super-admin.
// Usage: php tools/make_super.php <email>
// (No accounts are seeded in code — register the user in the app first.)
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "CLI only.\n"); exit(1); }
$email = trim((string)($argv[1] ?? ''));
if ($email === '') { fwrite(STDERR, "Usage: php tools/make_super.php <email>\n"); exit(1); }

require dirname(__DIR__) . '/lib.php';
$pdo = db();
$st = $pdo->prepare('SELECT id, name, email, role FROM users WHERE email = ?');
$st->execute([$email]);
$u = $st->fetch();
if (!$u) { fwrite(STDERR, "No registered user with email $email — register them in the app first.\n"); exit(1); }
if (($u['role'] ?? 'owner') === 'super') {
    echo "{$u['name']} <$email> is already a super-admin.\n";
    exit(0);
}
$pdo->prepare("UPDATE users SET role = 'super' WHERE id = ?")->execute([(int)$u['id']]);
echo "Promoted {$u['name']} <$email> to super-admin.\n";
