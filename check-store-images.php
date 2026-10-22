<?php
// Check store menu images. DELETE AFTER RUN.
require __DIR__ . '/config.php';

$stores = ['tandoor-nights', 'brew-and-bean', 'maison-olive', 'smash-bros'];
foreach ($stores as $slug) {
    echo "=== $slug ===\n";
    $jsonFile = __DIR__ . "/stores/$slug/content.json";
    if (!file_exists($jsonFile)) {
        echo "  content.json NOT FOUND\n";
        continue;
    }
    $data = json_decode(file_get_contents($jsonFile), true);
    $missing = 0; $total = 0; $broken = [];
    foreach ($data['menu']['categories'] ?? [] as $cat) {
        foreach ($cat['items'] ?? [] as $item) {
            $total++;
            $img = $item['image'] ?? '';
            if (!$img) {
                $missing++;
            } else {
                // Check if image file exists in store folder
                $imgPath = __DIR__ . "/stores/$slug/$img";
                if (!file_exists($imgPath)) {
                    $broken[] = $item['name'] . " -> $img";
                }
            }
        }
    }
    echo "  Total: $total, No image field: $missing, Broken path: " . count($broken) . "\n";
    foreach (array_slice($broken, 0, 5) as $b) echo "    BROKEN: $b\n";
}
echo "DONE.\n";
@unlink(__FILE__);
