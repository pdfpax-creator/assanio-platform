<?php
// Copy menu images from templates to stores. DELETE AFTER RUN.
require __DIR__ . '/config.php';

$map = [
    'tandoor-nights' => 'desi-bbq',
    'brew-and-bean'  => 'cafe',
    'maison-olive'   => 'fine-dining',
    'smash-bros'     => 'pizza-fastfood',
];

foreach ($map as $store => $tpl) {
    echo "=== $store (from $tpl) ===\n";
    
    // Load template images by item name
    $tplData = json_decode(file_get_contents(__DIR__ . "/templates/$tpl/content.json"), true);
    $tplImages = [];
    foreach ($tplData['menu']['categories'] ?? [] as $cat) {
        foreach ($cat['items'] ?? [] as $item) {
            if (!empty($item['image'])) {
                $tplImages[$item['name']] = $item['image'];
            }
        }
    }
    
    // Load store content
    $storeFile = __DIR__ . "/stores/$store/content.json";
    $storeData = json_decode(file_get_contents($storeFile), true);
    
    $fixed = 0;
    foreach ($storeData['menu']['categories'] ?? [] as &$cat) {
        foreach ($cat['items'] ?? [] as &$item) {
            if (empty($item['image']) && isset($tplImages[$item['name']])) {
                $item['image'] = $tplImages[$item['name']];
                $fixed++;
            }
        }
    }
    
    file_put_contents($storeFile, json_encode($storeData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    echo "  Fixed $fixed items\n";
}
echo "DONE.\n";
@unlink(__FILE__);
