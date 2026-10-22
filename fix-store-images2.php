<?php
// Copy menu images from templates to stores - FIXED version. DELETE AFTER RUN.
require __DIR__ . '/config.php';

$map = [
    'tandoor-nights' => 'desi-bbq',
    'brew-and-bean'  => 'cafe',
    'maison-olive'   => 'fine-dining',
    'smash-bros'     => 'pizza-fastfood',
];

foreach ($map as $store => $tpl) {
    echo "=== $store (from $tpl) ===\n";
    
    $tplData = json_decode(file_get_contents(__DIR__ . "/templates/$tpl/content.json"), true);
    $tplImages = [];
    foreach ($tplData['menu']['categories'] as $cat) {
        foreach ($cat['items'] as $item) {
            if (!empty($item['image'])) {
                $tplImages[$item['name']] = $item['image'];
            }
        }
    }
    
    $storeFile = __DIR__ . "/stores/$store/content.json";
    $storeData = json_decode(file_get_contents($storeFile), true);
    
    $fixed = 0; $unmatched = [];
    $numCats = count($storeData['menu']['categories']);
    for ($ci = 0; $ci < $numCats; $ci++) {
        $numItems = count($storeData['menu']['categories'][$ci]['items']);
        for ($ii = 0; $ii < $numItems; $ii++) {
            $name = $storeData['menu']['categories'][$ci]['items'][$ii]['name'];
            $cur = $storeData['menu']['categories'][$ci]['items'][$ii]['image'] ?? '';
            if (empty($cur) && isset($tplImages[$name])) {
                $storeData['menu']['categories'][$ci]['items'][$ii]['image'] = $tplImages[$name];
                $fixed++;
            } elseif (empty($cur)) {
                $unmatched[] = $name;
            }
        }
    }
    
    file_put_contents($storeFile, json_encode($storeData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    echo "  Fixed: $fixed, Unmatched: " . count($unmatched) . "\n";
    foreach ($unmatched as $u) echo "    NO MATCH: $u\n";
}
echo "DONE.\n";
