<?php
// NOORÉ template — admin config
// DEFAULT ADMIN PASSWORD: noore123 (documented in README only, never on the login page)
// Change it: run  php -r 'echo password_hash("NEW_PASSWORD", PASSWORD_DEFAULT), "\n";'
// and paste the output below, replacing the hash.
define('CH_ADMIN_HASH', '$2y$10$EVlQkX0Y.IfNLica4S10gecsAxWw3yGehytekRg66HX1y6xzm5ReW');
define('CH_SESSION_KEY', 'ch_admin_auth_v1');
define('CH_CONTENT_FILE', dirname(__DIR__) . '/content.json');
define('CH_IMAGES_DIR', dirname(__DIR__) . '/images');
define('CH_ORDERS_FILE', dirname(__DIR__) . '/api/orders.json');
