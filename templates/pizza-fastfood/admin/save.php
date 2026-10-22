<?php
// Admin save endpoint: login, save content.json, image uploads.
session_start();
require __DIR__ . '/config.php';
header('Content-Type: application/json');

function authed() {
    return !empty($_SESSION[CH_SESSION_KEY]);
}
function fail($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// --- LOGIN ---
if ($action === 'login') {
    $pw = $_POST['password'] ?? '';
    if ($pw !== '' && password_verify($pw, CH_ADMIN_HASH)) {
        $_SESSION[CH_SESSION_KEY] = true;
        echo json_encode(['ok' => true]);
    } else {
        fail('Wrong password.', 401);
    }
    exit;
}

// --- LOGOUT ---
if ($action === 'logout') {
    unset($_SESSION[CH_SESSION_KEY]);
    echo json_encode(['ok' => true]);
    exit;
}

if (!authed()) fail('Not logged in.', 401);

// --- SAVE content.json (atomic write) ---
if ($action === 'save') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) fail('Invalid JSON.');
    // sanity: must look like our content schema
    if (empty($data['site']['name']) || empty($data['menu']['categories'])) {
        fail('Content failed validation (site.name / menu.categories missing).');
    }
    $tmp = CH_CONTENT_FILE . '.tmp';
    if (file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) === false) {
        fail('Could not write temp file.', 500);
    }
    if (!rename($tmp, CH_CONTENT_FILE)) fail('Could not replace content.json.', 500);
    echo json_encode(['ok' => true]);
    exit;
}

// --- IMAGE UPLOAD ---
if ($action === 'upload') {
    if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        fail('Upload failed.');
    }
    $f = $_FILES['file'];
    if ($f['size'] > 2 * 1024 * 1024) fail('Image must be under 2MB.');
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) fail('Only JPG, PNG or WebP images.');
    // verify it is really an image
    if (@getimagesize($f['tmp_name']) === false) fail('File is not a valid image.');
    $base = preg_replace('/[^a-z0-9_-]/i', '-', pathinfo($f['name'], PATHINFO_FILENAME));
    $base = substr($base === '' ? 'img' : $base, 0, 40);
    $name = $base . '-' . bin2hex(random_bytes(4)) . '.' . $ext; // basename only, no traversal
    $dest = CH_IMAGES_DIR . '/' . $name;
    if (!is_dir(CH_IMAGES_DIR)) mkdir(CH_IMAGES_DIR, 0755, true);
    if (!move_uploaded_file($f['tmp_name'], $dest)) fail('Could not store image.', 500);
    echo json_encode(['ok' => true, 'file' => 'images/' . $name]);
    exit;
}

// --- ORDERS: list (admin inbox) ---
if ($action === 'orders') {
    $orders = [];
    if (file_exists(CH_ORDERS_FILE)) {
        $old = json_decode(@file_get_contents(CH_ORDERS_FILE), true);
        if (is_array($old)) $orders = $old;
    }
    echo json_encode(['ok' => true, 'orders' => $orders]);
    exit;
}

// --- ORDERS: mark completed / delete ---
if ($action === 'order_status' || $action === 'order_delete') {
    $id = (string)($_POST['id'] ?? '');
    if ($id === '') fail('Missing order id.');
    $orders = [];
    if (file_exists(CH_ORDERS_FILE)) {
        $old = json_decode(@file_get_contents(CH_ORDERS_FILE), true);
        if (is_array($old)) $orders = $old;
    }
    $found = false;
    foreach ($orders as $k => $o) {
        if (($o['id'] ?? '') === $id) {
            $found = true;
            if ($action === 'order_status') {
                $st = $_POST['status'] ?? '';
                if (!in_array($st, ['new', 'completed'], true)) fail('Bad status.');
                $orders[$k]['status'] = $st;
            } else {
                unset($orders[$k]);
                $orders = array_values($orders);
            }
            break;
        }
    }
    if (!$found) fail('Order not found.', 404);
    $tmp = CH_ORDERS_FILE . '.tmp';
    if (file_put_contents($tmp, json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) === false) {
        fail('Could not update orders.', 500);
    }
    if (!rename($tmp, CH_ORDERS_FILE)) fail('Could not update orders.', 500);
    echo json_encode(['ok' => true]);
    exit;
}

fail('Unknown action.');
