<?php
// One-time image uploader for real-estate template. DELETE AFTER USE.
$targetDir = __DIR__ . '/templates/real-estate/images/';
if (!is_dir($targetDir)) { mkdir($targetDir, 0755, true); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['img'])) {
    $f = $_FILES['img'];
    $name = preg_replace('/[^a-z0-9\-\.]/i', '', basename($f['name']));
    if (move_uploaded_file($f['tmp_name'], $targetDir . $name)) {
        echo "OK:$name";
    } else {
        echo "FAIL:$name";
    }
    exit;
}
echo '<form method="post" enctype="multipart/form-data"><input type="file" name="img"><button>Upload</button></form>';
