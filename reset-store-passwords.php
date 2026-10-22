<?php
// Reset all store admin passwords. DELETE AFTER RUN.
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
        echo "$slug: config.php NOT FOUND\n";
        continue;
    }
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $content = file_get_contents($configFile);
    $newContent = preg_replace(
        "/define\('CH_ADMIN_HASH', '[^']*'\);/",
        "define('CH_ADMIN_HASH', '$hash');",
        $content
    );
    if ($newContent !== $content) {
        file_put_contents($configFile, $newContent);
        echo "$slug: password reset OK\n";
    } else {
        echo "$slug: FAILED to update hash\n";
    }
}
echo "DONE.\n";
@unlink(__FILE__);
