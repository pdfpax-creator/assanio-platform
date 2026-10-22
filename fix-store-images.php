<?php
// Update demo stores with menu images (preserves customizations) - run once, DELETE after
$slugs = ['tandoor-nights' => 'desi-bbq', 'brew-and-bean' => 'cafe', 'maison-olive' => 'fine-dining', 'smash-bros' => 'pizza-fastfood'];

echo "<pre>";
foreach ($slugs as $slug => $tpl) {
    $storeFile = __DIR__ . "/stores/$slug/content.json";
    $tplFile = __DIR__ . "/templates/$tpl/content.json";
    if (!file_exists($storeFile) || !file_exists($tplFile)) {
        echo "⏭️ $slug: skip\n"; continue;
    }
    $store = json_decode(file_get_contents($storeFile), true);
    $tplData = json_decode(file_get_contents($tplFile), true);

    // Build image map from template
    $imgMap = [];
    foreach ($tplData['menu']['categories'] ?? [] as $cat) {
        foreach ($cat['items'] ?? [] as $item) {
            if (!empty($item['image'])) $imgMap[$item['name']] = $item['image'];
        }
    }

    // Apply to store (only where missing)
    $fixed = 0;
    foreach ($store['menu']['categories'] ?? [] as &$cat) {
        foreach ($cat['items'] ?? [] as &$item) {
            if (empty($item['image']) && isset($imgMap[$item['name']])) {
                $item['image'] = $imgMap[$item['name']];
                $fixed++;
            }
        }
    }
    file_put_contents($storeFile, json_encode($store, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    echo "✅ $slug: $fixed images added\n";
}
echo "DONE! Delete this file.</pre>";
@unlink(__FILE__);
