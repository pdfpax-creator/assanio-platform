<?php
// Create 2 real test stores with Google Maps data. DELETE AFTER RUN.
require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';
$pdo = db();

$stores = [
    [
        'name' => 'BBQ Bazaar',
        'slug' => 'bbq-bazaar-test',
        'template' => 'desi-bbq',
        'whatsapp' => '92515579977',
        'city' => 'Rawalpindi',
        'address' => '29-C Plaza, Phase 4 Civic Center, Bahria Town, Rawalpindi',
        'phone' => '051 5579977',
        'hours' => '12:00 PM - 12:00 AM',
    ],
    [
        'name' => 'Downtown Slice',
        'slug' => 'downtown-slice-test',
        'template' => 'pizza-fastfood',
        'whatsapp' => '923165733222',
        'city' => 'Rawalpindi',
        'address' => 'Plot 49, Phase 4 Civic Centre, Bahria Town, Rawalpindi',
        'phone' => '0316 5733222',
        'hours' => '1:00 PM - 1:00 AM',
    ],
];

foreach ($stores as $s) {
    // Check if exists
    $st = $pdo->prepare("SELECT id FROM stores WHERE slug=?");
    $st->execute([$s['slug']]);
    if ($st->fetch()) {
        echo $s['slug'] . ": already exists, skipping\n";
        continue;
    }
    
    // Create store directory from template
    $tplDir = __DIR__ . '/templates/' . $s['template'];
    $storeDir = __DIR__ . '/stores/' . $s['slug'];
    if (!is_dir($storeDir)) mkdir($storeDir, 0755, true);
    
    // Copy template files
    $files = ['index.html', 'content.json', 'palettes.json'];
    foreach ($files as $f) {
        if (file_exists("$tplDir/$f")) copy("$tplDir/$f", "$storeDir/$f");
    }
    if (is_dir("$tplDir/images")) {
        if (!is_dir("$storeDir/images")) mkdir("$storeDir/images", 0755, true);
        foreach (glob("$tplDir/images/*") as $img) {
            if (is_file($img)) copy($img, "$storeDir/images/" . basename($img));
        }
    }
    if (is_dir("$tplDir/admin")) {
        if (!is_dir("$storeDir/admin")) mkdir("$storeDir/admin", 0755, true);
        foreach (glob("$tplDir/admin/*") as $af) {
            if (is_file($af)) copy($af, "$storeDir/admin/" . basename($af));
        }
    }
    
    // Customize content.json with real data
    $cj = json_decode(file_get_contents("$storeDir/content.json"), true);
    $cj['site']['name'] = $s['name'];
    $cj['site']['tagline'] = $s['template'] === 'desi-bbq' ? 'Authentic Desi BBQ & Traditional Flavours' : 'Best Pizza in Bahria Town';
    $cj['contact']['address'] = $s['address'];
    $cj['contact']['phone'] = $s['phone'];
    $cj['contact']['hours'] = $s['hours'];
    $cj['contact']['whatsapp'] = $s['whatsapp'];
    $cj['whatsapp']['number'] = $s['whatsapp'];
    if (isset($cj['site']['city'])) $cj['site']['city'] = $s['city'];
    file_put_contents("$storeDir/content.json", json_encode($cj, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // DB entry (owner_id 1 = super admin)
    $apiKey = bin2hex(random_bytes(16));
    $st = $pdo->prepare("INSERT INTO stores (owner_id, name, slug, template, whatsapp, city, api_key) VALUES (1,?,?,?,?,?,?)");
    $st->execute([$s['name'], $s['slug'], $s['template'], $s['whatsapp'], $s['city'], $apiKey]);
    
    // Admin password
    $adminDir = "$storeDir/admin";
    if (file_exists("$adminDir/config.php")) {
        $cfg = file_get_contents("$adminDir/config.php");
        $hash = password_hash('test123', PASSWORD_BCRYPT);
        $cfg = preg_replace("/'password_hash'\s*=>\s*'[^']*'/", "'password_hash' => '$hash'", $cfg);
        if (strpos($cfg, 'password_hash') === false) {
            $cfg = str_replace('<?php', "<?php\n// Admin password: test123", $cfg);
        }
        file_put_contents("$adminDir/config.php", $cfg);
    }
    
    echo $s['slug'] . ": CREATED ✅\n";
    echo "  URL: https://app.pdfpax.com/" . $s['slug'] . "/\n";
}

@unlink(__FILE__);
echo "\nDone!\n";
