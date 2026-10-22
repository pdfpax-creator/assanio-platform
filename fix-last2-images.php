<?php
// Assign images to the 2 unmatched Maison Olive items. DELETE AFTER RUN.
require __DIR__ . '/config.php';

$storeFile = __DIR__ . '/stores/maison-olive/content.json';
$data = json_decode(file_get_contents($storeFile), true);

$fixes = [
    'Maison Olive Special Pulao' => 'images/hero-dish.jpg',
    'Maison Olive Iced Chai' => 'images/hero-dish.jpg',
];

$fixed = 0;
foreach ($data['menu']['categories'] as &$cat) {
    foreach ($cat['items'] as &$item) {
        if (isset($fixes[$item['name']]) && empty($item['image'])) {
            $item['image'] = $fixes[$item['name']];
            $fixed++;
            echo "Fixed: {$item['name']}\n";
        }
    }
}

file_put_contents($storeFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
echo "Total fixed: $fixed. DONE.\n";
