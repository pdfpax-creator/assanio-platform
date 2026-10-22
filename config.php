<?php
// Assanio — central config. The platform's public name lives HERE only.
define('PLATFORM_NAME', 'Assanio');
define('PLATFORM_TAGLINE', 'Your restaurant website + online ordering — live in 48 hours.');

// ★★ CHANGE THIS when you move hosting/domain ★★
// This is YOUR platform's domain (where the admin panel lives).
// Customer CNAME records will point here.
define('PLATFORM_DOMAIN', 'app.pdfpax.com');

define('RP_ROOT', __DIR__);
define('RP_DATA_DIR', __DIR__ . '/data');
define('RP_DB_FILE', RP_DATA_DIR . '/platform.sqlite');
define('RP_STORES_DIR', __DIR__ . '/stores');
define('RP_TEMPLATES_DIR', __DIR__ . '/templates');

// The 4 launch templates (key = folder name under templates/).
define('RP_TEMPLATES', [
    'pizza-fastfood' => ['name' => 'Crust House',     'label' => 'Crust House — Pizza & Fast Food', 'blurb' => 'Bold, fast, deal-driven. Built for pizza & fast-food outlets.', 'img' => 'assets/landing/t1-pizza.jpg'],
    'desi-bbq'       => ['name' => 'Dera BBQ House',  'label' => 'Dera BBQ House — Desi BBQ',       'blurb' => 'Warm and hearty. BBQ, karahi and family platters.',             'img' => 'assets/landing/t2-dera.jpg'],
    'cafe'           => ['name' => 'The Quiet Cup',   'label' => 'The Quiet Cup — Cafe & Bakery',   'blurb' => 'Calm and minimal. Coffee, bakes and slow mornings.',            'img' => 'assets/landing/t3-cafe.jpg'],
    'fine-dining'    => ['name' => 'NOORÉ',           'label' => 'NOORÉ — Fine Dining',             'blurb' => 'Dark, gold, premium. Fine dining and tasting menus.',          'img' => 'assets/landing/t4-noore.jpg'],
]);

define('RP_ORDER_STATUSES', ['new', 'accepted', 'preparing', 'delivered', 'cancelled']);
