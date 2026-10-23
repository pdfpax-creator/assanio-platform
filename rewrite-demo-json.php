<?php
// Rewrite demo content.json cleanly from template. DELETE AFTER RUN.
$tplFile = __DIR__ . '/templates/real-estate/content.json';
$storeFile = __DIR__ . '/stores/emerald-estates-demo/content.json';

$tpl = json_decode(file_get_contents($tplFile), true);
if ($tpl === null) { echo "Template JSON broken!\n"; exit; }

// Use template as base (it has correct structure)
$store = $tpl;

// Fix image paths to absolute (template has relative)
$store['hero']['image'] = '/templates/real-estate/images/hero-villa.jpg';
foreach ($store['properties']['properties'] as $i => &$p) {
    $p['image'] = '/templates/real-estate/images/prop-' . ($i+1) . '.jpg';
}
foreach ($store['agents']['items'] as $i => &$a) {
    $a['image'] = '/templates/real-estate/images/agent-' . ($i+1) . '.jpg';
}
$store['about']['image'] = '/templates/real-estate/images/hero-villa.jpg';

// Write cleanly
$json = json_encode($store, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if (file_put_contents($storeFile, $json) !== false) {
    echo "Rewrote OK (" . strlen($json) . " bytes)\n";
    // Verify
    $check = json_decode(file_get_contents($storeFile), true);
    echo $check === null ? "VERIFY FAILED\n" : "VERIFY OK\n";
} else {
    echo "Write failed\n";
}
@unlink(__FILE__);
