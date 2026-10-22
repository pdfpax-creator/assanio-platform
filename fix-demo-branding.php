<?php
// FIX DEMO STORE BRANDING - upload to app/, run once, DELETE after
require_once __DIR__ . '/lib.php';

$fixes = [
    'tandoor-nights' => [
        'Dera BBQ House' => 'Tandoor Nights',
        'Dera' => 'Tandoor Nights',
        'Bahria Town Phase 7' => 'DHA Phase 5, Lahore',
        '2025 Dera BBQ House' => '2026 Tandoor Nights',
    ],
    'brew-and-bean' => [
        'The Quiet Cup' => 'Brew & Bean',
        'Quiet Cup' => 'Brew & Bean',
        'F-7 Markaz, Islamabad' => 'F-7 Markaz, Islamabad',
        '2026 The Quiet Cup' => '2026 Brew & Bean',
    ],
    'maison-olive' => [
        'NOORÉ' => 'Maison Olive',
        'Nooré' => 'Maison Olive',
        'NOORE' => 'Maison Olive',
        'Islamabad' => 'Karachi',
        '2026 NOORÉ Islamabad' => '2026 Maison Olive, Karachi',
        '"monogram": "N"' => '"monogram": "M"',
    ],
    'smash-bros' => [
        'Crust House' => 'Smash Bros',
        'wood-fired oven' => 'smash grill',
        '2025 Crust House' => '2026 Smash Bros',
    ],
];

echo "<h2>🎨 Fixing demo store branding...</h2><pre>";

foreach ($fixes as $slug => $replacements) {
    $file = __DIR__ . '/stores/' . $slug . '/content.json';
    if (!file_exists($file)) {
        echo "⏭️ $slug: content.json not found\n";
        continue;
    }
    $content = file_get_contents($file);
    $original = $content;
    foreach ($replacements as $old => $new) {
        $content = str_replace($old, $new, $content);
    }
    if ($content !== $original) {
        file_put_contents($file, $content);
        echo "✅ $slug: branding fixed\n";
    } else {
        echo "⏭️ $slug: no changes needed\n";
    }
}

echo "\n✅ DONE! Delete this file now.\n</pre>";
