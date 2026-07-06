<?php
// يولّد public/icon.ico بسيط (256x256 PNG داخل ICO)
$size = 256;
$im = imagecreatetruecolor($size, $size);
imagesavealpha($im, true);

$bg = imagecolorallocate($im, 16, 185, 129);  // أخضر صيدلية
$white = imagecolorallocate($im, 255, 255, 255);

imagefilledrectangle($im, 0, 0, $size, $size, $bg);

// صليب طبي أبيض
$cw = 60;  // عرض الصليب
$cl = 160; // طول الصليب
$cx = $size / 2;
$cy = $size / 2;
imagefilledrectangle($im, $cx - $cw/2, $cy - $cl/2, $cx + $cw/2, $cy + $cl/2, $white);
imagefilledrectangle($im, $cx - $cl/2, $cy - $cw/2, $cx + $cl/2, $cy + $cw/2, $white);

$pngPath = __DIR__ . '/icon-tmp.png';
imagepng($im, $pngPath);
imagedestroy($im);

// التفاف PNG داخل ملف ICO
$png = file_get_contents($pngPath);
$pngLen = strlen($png);

$ico = pack('vvv', 0, 1, 1);  // header: reserved, type=1(ICO), count=1
$ico .= pack('CCCCvvVV',
    0,        // width 0 = 256
    0,        // height 0 = 256
    0,        // colors
    0,        // reserved
    1,        // color planes
    32,       // bits per pixel
    $pngLen,  // size of image data
    22        // offset (header 6 + entry 16)
);
$ico .= $png;

file_put_contents(__DIR__ . '/../public/icon.ico', $ico);
unlink($pngPath);
echo "Created public/icon.ico (" . strlen($ico) . " bytes)\n";
