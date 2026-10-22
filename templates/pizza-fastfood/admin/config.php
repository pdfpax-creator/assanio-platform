<?php
// Crust House template — admin config
// DEFAULT ADMIN PASSWORD: crust123
// Change it: run  php -r 'echo password_hash("NEW_PASSWORD", PASSWORD_DEFAULT), "\n";'
// and paste the output below, replacing the hash.
define('CH_ADMIN_HASH', '$2y$10$gYwxbYFrxYnIe7UBzRGKKu4cAWqYKG/8CB/oFUF/U6pb81/PDbSjC');
define('CH_SESSION_KEY', 'ch_admin_auth_v1');
define('CH_CONTENT_FILE', dirname(__DIR__) . '/content.json');
define('CH_IMAGES_DIR', dirname(__DIR__) . '/images');
define('CH_ORDERS_FILE', dirname(__DIR__) . '/api/orders.json');
