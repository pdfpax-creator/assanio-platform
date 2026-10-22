<?php
// ONE-TIME super-admin bootstrap for Assanio platform. Promotes one email, then SELF-DELETES.
$t = $_GET['t'] ?? '';
if (!hash_equals('666fa5564e5ddb067bc79221ace88125', $t)) { http_response_code(403); exit('forbidden'); }
require __DIR__ . '/lib.php';
$email = trim((string)($_GET['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { http_response_code(400); exit('bad email'); }
$pdo = db();
$st = $pdo->prepare('SELECT id, role FROM users WHERE email = ?');
$st->execute([$email]); $u = $st->fetch();
if (!$u) { http_response_code(404); exit('no such user - register first'); }
$pdo->prepare("UPDATE users SET role = 'super' WHERE id = ?")->execute([(int)$u['id']]);
@unlink(__FILE__);
header('Content-Type: application/json');
echo json_encode(['ok' => true, 'email' => $email, 'role' => 'super']);
