<?php
// Emerald Estates (real-estate) template — admin config
// DEFAULT ADMIN PASSWORD: emerald123 (documented in README.md only — never shown on the login page)
// Change it: run  php -r 'echo password_hash("NEW_PASSWORD", PASSWORD_DEFAULT), "\n";'
// and paste the output below, replacing the hash.
define('CH_ADMIN_HASH', '$2y$10$5l9tTGrGaB.09UZ5NO8LJueJ1yeH6UixQzcqWGyIp3tewQikXhGNa');
define('CH_SESSION_KEY', 're_admin_auth_v1');
define('CH_CONTENT_FILE', dirname(__DIR__) . '/content.json');
define('CH_IMAGES_DIR', dirname(__DIR__) . '/images');
define('CH_ORDERS_FILE', dirname(__DIR__) . '/api/orders.json');
