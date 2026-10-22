<?php
require_once __DIR__ . '/lib.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    logout_user();
}
header('Location: index.php');
exit;
