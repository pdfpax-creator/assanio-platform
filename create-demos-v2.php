<?php
// CREATE 4 IMPRESSIVE DEMO STORES - upload to app/, run once, DELETE after
require_once __DIR__ . '/lib.php';

$pdo = db();
$ownerId = (int)$pdo->query("SELECT id FROM users WHERE email='mrmujtabaawan@gmail.com'")->fetchColumn();

$demos = [
    [
        'name' => 'Tandoor Nights', 'slug' => 'tandoor-nights', 'template' => 'desi-bbq',
        'whatsapp' => '03001234567', 'city' => 'Lahore',
        'tagline' => 'Authentic desi BBQ, straight from the tandoor',
    ],
    [
        'name' => 'Brew & Bean', 'slug' => 'brew-and-bean', 'template' => 'cafe',
        'whatsapp' => '03001234567', 'city' => 'Islamabad',
        'tagline' => 'Specialty coffee & fresh bakes, brewed with love',
    ],
    [
        'name' => 'Maison Olive', 'slug' => 'maison-olive', 'template' => 'fine-dining',
        'whatsapp' => '03001234567', 'city' => 'Karachi',
        'tagline' => 'Mediterranean fine dining in the heart of Karachi',
    ],
    [
        'name' => 'Smash Bros', 'slug' => 'smash-bros', 'template' => 'pizza-fastfood',
        'whatsapp' => '03001234567', 'city' => 'Rawalpindi',
        'tagline' => 'Smashed burgers, loaded fries, zero regrets',
    ],
];

echo "<h2>🏗️ Creating demo stores...</h2><pre>";

foreach ($demos as $d) {
    // Check if exists
    $exists = $pdo->prepare("SELECT id FROM stores WHERE slug=?");
    $exists->execute([$d['slug']]);
    if ($exists->fetch()) {
        echo "⏭️ {$d['name']} already exists, skipping\n";
        continue;
    }

    $apiKey = bin2hex(random_bytes(16));
    $ins = $pdo->prepare('INSERT INTO stores (owner_id, name, slug, template, whatsapp, city, api_key) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $ins->execute([$ownerId, $d['name'], $d['slug'], $d['template'], $d['whatsapp'], $d['city'], $apiKey]);
    $storeId = (int)$pdo->lastInsertId();

    $store = [
        'id' => $storeId, 'name' => $d['name'], 'slug' => $d['slug'],
        'template' => $d['template'], 'whatsapp' => $d['whatsapp'], 'city' => $d['city'],
    ];

    try {
        generateStoreFiles($store);
        echo "✅ {$d['name']} created: /{$d['slug']}/\n";
    } catch (Throwable $ex) {
        echo "❌ {$d['name']} failed: " . $e($ex->getMessage()) . "\n";
    }
}

echo "\n✅ DONE! Delete this file now.\n</pre>";

function e($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
