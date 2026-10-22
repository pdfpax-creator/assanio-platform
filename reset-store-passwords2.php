<?php
// Reset store passwords - FIXED (handles $ in bcrypt). DELETE AFTER RUN.
require __DIR__ . '/config.php';

$passwords = [
    'tandoor-nights' => '12351f828f',
    'brew-and-bean'  => '9882642967',
    'maison-olive'   => '6ac2832f76',
    'smash-bros'     => '98d75b86eb',
    'downtown-slice' => '4950b7c670',
    'br9-cafe'       => 'dcddfa110b',
];

foreach ($passwords as $slug => $pass) {
    $configFile = __DIR__ . "/stores/$slug/admin/config.php";
    if (!file_exists($configFile)) {
        echo "$slug: NOT FOUND\n";
        continue;
    }
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $lines = file($configFile);
    $updated = false;
    foreach ($lines as $i => $line) {
        if (strpos($line, "define('CH_ADMIN_HASH'") !== false) {
            $lines[$i] = "define('CH_ADMIN_HASH', '" . $hash . "');\n";
            $updated = true;
            break;
        }
    }
    if ($updated) {
        file_put_contents($configFile, implode('', $lines));
        // Verify it worked
        $check = file_get_contents($configFile);
        if (strpos($check, $hash) !== false && password_verify($pass, $hash)) {
            echo "$slug: OK (verified)\n";
        } else {
            echo "$slug: WRITE OK but verify failed\n";
        }
    } else {
        echo "$slug: CH_ADMIN_HASH line not found\n";
    }
}
echo "DONE.\n";
@unlink(__FILE__);
