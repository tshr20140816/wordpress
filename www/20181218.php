<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s'));

$mu = new MyUtils();

$url = 'http://www.motomachi-pa.jp/cgi/manku.pl?park_id=1&mode=pc';
$res = $mu->get_contents($url);

$im1 = imagecreatefromstring($res);
imagefilter($im1, IMG_FILTER_NEGATE);
$file = '/tmp/motomachi_parking_information.png';

header('Content-type: image/png');
//imagepng($im1, $file);
imagepng($im1);
imagedestroy($im1);



?>
