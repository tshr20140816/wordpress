<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s'));

$mu = new MyUtils();

$url = 'http://www.motomachi-pa.jp/cgi/manku.pl?park_id=1&mode=pc';
$res = $mu->get_contents($url);

$im1 = imagecreatefromstring($res);
imagefilter($im1, IMG_FILTER_GRAYSCALE);

$im2 = imagecreatetruecolor(imagesx($im1), imagesy($im1));
for($x = 0; $x < imagesx($im1); ++$x)
{
  for($y = 0; $y < imagesy($im1); ++$y)
  {
    $index = imagecolorat($im1, $x, $y);
    $rgb = imagecolorsforindex($im1, $index);
    // error_log(print_r($rgb, TRUE));
    // $color = imagecolorallocate($im2, 255 - $rgb['red'], 255 - $rgb['green'], 255 - $rgb['blue']);
    if ($rgb['red'] == 138 && $rgb['green'] == 138 && $rgb['blue'] == 138) {
      $color = imagecolorallocate($im2, 255, 255, 255);
    } else {
      $color = imagecolorallocate($im2, 0, 0, 0);
    }

    imagesetpixel($im2, $x, $y, $color);
  }
}

$file = '/tmp/motomachi_parking_information.png';

header('Content-type: image/png');
//imagepng($im1, $file);
imagepng($im2);
imagedestroy($im1);
imagedestroy($im2);



?>
