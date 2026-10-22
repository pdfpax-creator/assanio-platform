<?php
// ONE-TIME deploy diagnostic for Assanio platform. Delete after use (?t=666fa5564e5ddb067bc79221ace88125EN&die=1).
$t = $_GET['t'] ?? '';
if (!hash_equals('666fa5564e5ddb067bc79221ace88125', $t)) { http_response_code(403); exit('forbidden'); }
if (isset($_GET['die'])) { @unlink(__FILE__); exit('deleted'); }
header('Content-Type: application/json');
$d = __DIR__ . '/data';
echo json_encode(['php' => PHP_VERSION,
  'pdo_drivers' => class_exists('PDO') ? PDO::getAvailableDrivers() : [],
  'data_dir_writable' => is_writable($d)]);
