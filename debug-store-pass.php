<?php
// Debug: check store config. DELETE AFTER RUN.
$slug = $_GET['store'] ?? 'tandoor-nights';
$configFile = __DIR__ . "/stores/$slug/admin/config.php";
if (!file_exists($configFile)) {
    echo "NOT FOUND: $configFile\n";
    exit;
}
$content = file_get_contents($configFile);
// Show the CH_ADMIN_HASH line (first 20 chars of hash only for security)
if (preg_match("/define\('CH_ADMIN_HASH', '(.{20})/", $content, $m)) {
    echo "Hash starts with: " . $m[1] . "...\n";
    echo "Hash length ok: " . (strpos($content, 'CH_ADMIN_HASH') !== false ? 'yes' : 'no') . "\n";
} else {
    echo "CH_ADMIN_HASH not found in expected format\n";
}
// Test: does password_verify work with our password?
require $configFile;
$test = password_verify('12351f828f', CH_ADMIN_HASH);
echo "Password verify test: " . ($test ? 'PASS' : 'FAIL') . "\n";
echo "DONE.\n";
@unlink(__FILE__);
