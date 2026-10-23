<?php
// Sync demo store content.json from template. DELETE AFTER RUN.
$tpl = json_decode(file_get_contents(__DIR__ . '/templates/real-estate/content.json'), true);
$storeFile = __DIR__ . '/stores/emerald-estates-demo/content.json';
$store = json_decode(file_get_contents($storeFile), true);

// Copy image paths and prompts from template
$store['hero']['image'] = $tpl['hero']['image'];
$store['hero']['imageAlt'] = $tpl['hero']['imageAlt'];
$store['hero']['subtext'] = $tpl['hero']['subtext'];

// Update property images to use template images (absolute path)
foreach ($store['properties']['properties'] as $i => &$p) {
    $p['image'] = '/app/templates/real-estate/images/prop-' . ($i+1) . '.jpg';
}
// Update agent images
$agents = &$store['agents']['items'];
foreach ($agents as $i => &$a) {
    $a['image'] = '/app/templates/real-estate/images/agent-' . ($i+1) . '.jpg';
}
// About image
$store['about']['image'] = '/app/templates/real-estate/images/hero-villa.jpg';
// Copy image prompts
$store['imagePrompts'] = $tpl['imagePrompts'];

file_put_contents($storeFile, json_encode($store, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Demo store content.json updated!\n";
@unlink(__FILE__);
