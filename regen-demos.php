<?php
// Regenerate demo store files - upload to app/, run once, DELETE after
require_once __DIR__ . '/lib.php';

$pdo = db();
$slugs = ['lahore-bbq-demo', 'chai-sutta-demo', 'maison-olive-demo'];

echo "<h2>Regenerating demo stores...</h2>";

foreach ($slugs as $slug) {
    $st = $pdo->prepare("SELECT * FROM stores WHERE slug=?");
    $st->execute([$slug]);
    $store = $st->fetch(PDO::FETCH_ASSOC);
    
    if (!$store) {
        echo "⏭️ $slug not found<br>";
        continue;
    }
    
    try {
        generateStoreFiles($store);
        echo "✅ $slug regenerated — <a href='/$slug/'>/$slug/</a><br>";
    } catch (Throwable $ex) {
        echo "❌ $slug: " . htmlspecialchars($ex->getMessage()) . "<br>";
    }
}

echo "<h3>DONE! Delete this file now.</h3>";
