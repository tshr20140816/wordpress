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

error_log(imagesx($im));
error_log(imagesy($im));

$im2 = imagecrop($im, ['x' => 0, 'y' => 95, 'width' => imagesx($im), 'height' => imagesy($im) - 145]);
// imagejpeg($im2, $file, 100);
$res = imagejpeg($im2, NULL, 100);

/*
header('Content-Type: image/jpeg');
echo file_get_contents($file);
*/

$url = 'https://api.ocr.space/parse/image';

//$post_data = ['base64image' => 'data:image/jpg;base64,' . base64_encode(file_get_contents($file))];
$post_data = ['base64image' => 'data:image/jpg;base64,' . base64_encode($res)];

$options = [
  CURLOPT_POST => TRUE,
  CURLOPT_HTTPHEADER => ['apiKey: ' . getenv('OCRSPACE_APIKEY')],
  CURLOPT_POSTFIELDS => http_build_query($post_data),
  ];

$res = $mu->get_contents($url, $options);

$data = json_decode($res);
error_log(print_r($data, TRUE));

imagedestroy($im);
imagedestroy($im2);
?>
