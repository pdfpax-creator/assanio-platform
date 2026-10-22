<?php
// POLISH DEMO STORES - upload to app/, run once, DELETE after
require_once __DIR__ . '/lib.php';

echo "<h2>✨ Polishing demo stores...</h2><pre>";

$stores = [
    'tandoor-nights' => [
        'replacements' => [
            'the dera treatment' => 'the royal treatment',
            'the dera classic' => 'the house classic',
            'a complete dera dinner' => 'a complete family dinner',
            'The full dera spread' => 'The full Tandoor spread',
            'Demo Experience for DHA Phase 5, Lahore' => 'DHA Phase 5, Lahore',
            'Sample reviews for template demonstration — replace with real customer feedback.' => '',
            'Sample reviews for template demonstration' => '',
        ],
        'prices' => [
            'Chicken White Handi' => 850,
            'Mint Raita' => 120,
            'Green Salad' => 180,
            'Kulfi' => 150,
            'Shahi Kheer' => 200,
        ],
    ],
    'brew-and-bean' => [
        'replacements' => [
            'QUIET VALUE' => 'BREW VALUES',
            'Quiet Value' => 'Brew Values',
            'Demo Experience for F-7 Markaz, Islamabad' => 'F-7 Markaz, Islamabad',
            'Sample reviews for template demonstration — replace with real customer feedback.' => '',
            'Sample reviews for template demonstration' => '',
        ],
        'prices' => [
            'Affogato' => 450,
            'Masala Chai' => 180,
            'Mint Lemonade' => 220,
            'Chicken Paratha Roll' => 320,
            'Lotus Cheesecake Slice' => 480,
            'Saffron Milk Cake' => 420,
        ],
    ],
    'maison-olive' => [
        'replacements' => [
            '"monogram": "N"' => '"monogram": "M"',
            'Kohsar Market, Karachi' => 'DHA Phase 6, Karachi',
            'Shop 4, Kohsar Market, F-6/3, Karachi' => 'Shop 4, Khayaban-e-Ittehad, DHA Phase 6, Karachi',
            'F-6/3, Karachi' => 'DHA Phase 6, Karachi',
            'F-7, Karachi' => 'DHA Phase 5, Karachi',
            'E-7, Karachi' => 'Clifton Block 4, Karachi',
            'Blue Area, Karachi' => 'Gulshan-e-Iqbal, Karachi',
            'Demo Experience for Karachi' => 'DHA Phase 6, Karachi',
            'Sample reviews for template demonstration — replace with real guest feedback.' => '',
            'Sample reviews for template demonstration' => '',
        ],
        'prices' => [
            'Malai Boti Skewers' => 950,
            'Saffron Biryani' => 780,
        ],
    ],
    'smash-bros' => [
        'replacements' => [
            'Crust House pizza' => 'Smash Bros burger',
            'Crust House' => 'Smash Bros',
            'Demo Experience for Bahria Town Phase 4' => 'Bahria Town Phase 4, Rawalpindi',
            'Sample reviews for template demonstration — replace with real customer feedback.' => '',
            'Sample reviews for template demonstration' => '',
            '© 2025 Smash Bros.' => '© 2026 Smash Bros.',
        ],
        'prices' => [
            'Loaded Fries' => 380,
        ],
    ],
];

foreach ($stores as $slug => $fix) {
    $file = __DIR__ . '/stores/' . $slug . '/content.json';
    if (!file_exists($file)) {
        echo "⏭️ $slug: not found\n";
        continue;
    }
    $content = file_get_contents($file);
    $data = json_decode($content, true);
    if (!$data) {
        echo "❌ $slug: invalid JSON\n";
        continue;
    }

    // Text replacements
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    foreach ($fix['replacements'] as $old => $new) {
        $json = str_replace($old, $new, $json);
    }
    $data = json_decode($json, true);

    // Fix missing prices in menu items
    if (isset($data['menu']) && is_array($data['menu'])) {
        foreach ($data['menu'] as &$category) {
            if (isset($category['items']) && is_array($category['items'])) {
                foreach ($category['items'] as &$item) {
                    $name = $item['name'] ?? '';
                    if ((empty($item['price']) || $item['price'] == 0) && isset($fix['prices'][$name])) {
                        $item['price'] = $fix['prices'][$name];
                    }
                }
            }
        }
    }

    // Fix copyright year to 2026
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $json = str_replace('© 2025 Tandoor Nights', '© 2026 Tandoor Nights', $json);

    file_put_contents($file, $json);
    echo "✅ $slug: polished\n";
}

echo "\n✅ DONE! Delete this file now.\n</pre>";
