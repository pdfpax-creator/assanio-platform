<?php
// Fix broken JSON escapes in demo store. DELETE AFTER RUN.
$file = __DIR__ . '/stores/emerald-estates-demo/content.json';
$raw = file_get_contents($file);
// Fix backslash issues: replace \/ with / and \i with /i etc.
$raw = str_replace('\\/', '/', $raw);
$raw = str_replace('\\', '/', $raw);
// Validate
$data = json_decode($raw, true);
if ($data === null) {
    echo "STILL BROKEN: " . json_last_error_msg() . "\n";
    exit;
}
// Fix image paths to be correct
foreach ($data['properties']['properties'] as $i => &$p) {
    $p['image'] = '/templates/real-estate/images/prop-' . ($i+1) . '.jpg';
}
foreach ($data['agents']['items'] as $i => &$a) {
    $a['image'] = '/templates/real-estate/images/agent-' . ($i+1) . '.jpg';
}
$data['about']['image'] = '/templates/real-estate/images/hero-villa.jpg';
$data['hero']['image'] = '/templates/real-estate/images/hero-villa.jpg';

file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo "Fixed! JSON valid.\n";
@unlink(__FILE__);
