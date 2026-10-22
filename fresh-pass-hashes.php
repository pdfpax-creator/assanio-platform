<?php
// Generate FRESH password hashes on server. NO self-delete.
$passwords = [
    'tandoor-nights' => '12351f828f',
    'brew-and-bean'  => '9882642967',
    'maison-olive'   => '6ac2832f76',
    'smash-bros'     => '98d75b86eb',
    'downtown-slice' => '4950b7c670',
    'br9-cafe'       => 'dcddfa110b',
];
foreach ($passwords as $slug => $pass) {
    $f = __DIR__ . "/stores/$slug/admin/config.php";
    if (!file_exists($f)) { echo "$slug: NOT FOUND\n"; continue; }
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $lines = file($f);
    foreach ($lines as $i => $line) {
        if (strpos($line, 'CH_ADMIN_HASH') !== false) {
            $lines[$i] = "define('CH_ADMIN_HASH', '" . $hash . "');\n";
            break;
        }
    }
    file_put_contents($f, implode('', $lines));
    // Verify by re-reading
    $verify_content = file_get_contents($f);
    preg_match("/define\('CH_ADMIN_HASH', '([^']+)'/", $verify_content, $m);
    $stored_hash = $m[1] ?? '';
    $ok = password_verify($pass, $stored_hash);
    echo "$slug: " . ($ok ? "OK-VERIFIED" : "FAIL") . "\n";
}
echo "DONE-NODELETE\n";
