<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$url = 'http://the-outlets-hiroshima.com/static/detail/car';
$res = $mu->get_contents($url);

$rc = preg_match('/<p id="parkingnow"><img src="(.+?)"/s', $res, $matches);
$res = $mu->get_contents($matches[1]);

$file = '/tmp/sample.jpg';
file_put_contents($file, $res);

$im = imagecreatefromjpeg($file);

// $size = min(imagesx($im), imagesy($im));

error_log(imagesx($im));
error_log(imagesy($im));

$im2 = imagecrop($im, ['x' => 0, 'y' => 0, 'width' => imagesx($im), 'height' => imagesy($im) / 2]);
imagejpeg($im2, $file);

file_get_contents($file);

?>
