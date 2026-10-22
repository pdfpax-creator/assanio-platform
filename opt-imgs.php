<?php
// Optimize landing images on server - run once, DELETE after
$images = [
    'assets/landing/hero-bbq.jpg' => [1200, 75],
    'assets/landing/t1-pizza.jpg' => [600, 70],
    'assets/landing/t2-dera.jpg' => [600, 70],
    'assets/landing/t3-cafe.jpg' => [600, 70],
    'assets/landing/t4-noore.jpg' => [600, 70],
];
echo "<pre>";
foreach ($images as $path => [$maxW, $q]) {
    $full = __DIR__ . '/' . $path;
    if (!file_exists($full)) { echo "⏭️ $path: not found\n"; continue; }
    $before = round(filesize($full)/1024);
    $info = getimagesize($full);
    if (!$info || $info[2] !== IMAGETYPE_JPEG) { echo "⏭️ $path: not JPEG\n"; continue; }

    $src = imagecreatefromjpeg($full);
    $w = imagesx($src); $h = imagesy($src);
    if ($w > $maxW) {
        $newW = $maxW; $newH = (int)($h * $maxW / $w);
        $dst = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($dst, $src, 0,0,0,0, $newW, $newH, $w, $h);
        imagedestroy($src); $src = $dst;
    }
    imagejpeg($src, $full, $q);
    imagedestroy($src);
    $after = round(filesize($full)/1024);
    echo "✅ $path: {$before}KB → {$after}KB\n";
}
echo "DONE!</pre>";
@unlink(__FILE__);
