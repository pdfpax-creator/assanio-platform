<?php
// Fix image paths in demo store. DELETE AFTER RUN.
$storeFile = __DIR__ . '/stores/emerald-estates-demo/content.json';
$store = json_decode(file_get_contents($storeFile), true);

foreach ($store['properties']['properties'] as $i => &$p) {
    $p['image'] = '/templates/real-estate/images/prop-' . ($i+1) . '.jpg';
}
$agents = &$store['agents']['items'];
foreach ($agents as $i => &$a) {
    $a['image'] = '/templates/real-estate/images/agent-' . ($i+1) . '.jpg';
}
$store['about']['image'] = '/templates/real-estate/images/hero-villa.jpg';
$store['hero']['image'] = '/templates/real-estate/images/hero-villa.jpg';

file_put_contents($storeFile, json_encode($store, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Fixed!\n";
@unlink(__FILE__);
# Fri Oct 23 10:54:21 PKT 2026
