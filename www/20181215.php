<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s'));

$mu = new MyUtils();

$url = 'http://the-outlets-hiroshima.com/static/detail/car';
$res = $mu->get_contents($url);

$rc = preg_match('/<p id="parkingnow"><img src="(.+?)"/s', $res, $matches);
$res = $mu->get_contents($matches[1]);

/*
$im1 : original
$im2 : 上段、下段カット 左右も少しカット
$im3 : サイズ 1/4
$im4 : Pマーク 除去 → png
*/
$im1 = imagecreatefromstring($res);

$im2 = imagecrop($im1, ['x' => 100, 'y' => 95, 'width' => imagesx($im1) - 200, 'height' => imagesy($im1) - 145]);
imagedestroy($im1);

$im3 = imagecreatetruecolor(imagesx($im2) / 4, imagesy($im2) / 4);
imagecopyresampled($im3, $im2, 0, 0, 0, 0, imagesx($im2) / 4, imagesy($im2) / 4, imagesx($im2), imagesy($im2));
imagedestroy($im2);

$check_point = 0;
for ($x = 0; $x < imagesx($im3); $x++) {
  $count = 0;
  for ($y = 0; $y < imagesy($im3); $y++) {
    $rgb = imagecolorat($im3, $x, $y);
    $r = ($rgb >> 16) & 0xFF;
    $g = ($rgb >> 8) & 0xFF;
    $b =  $rgb & 0xFF;
    if ($r > 200 && $g > 200 && $b > 200) {
      $count++;
    }
  }
  error_log($pid . ' $x $count : ' . $x . ' ' . $count);
  if ($check_point == 0 && $count < 15) {
    $check_point = 1;
  } else if ($check_point == 1 && $count > 15) {
    $check_point = $x;
    break;
  }
}

$im4 = imagecrop($im3, ['x' => $check_point, 'y' => 0, 'width' => imagesx($im3) - $check_point, 'height' => imagesy($im3)]);
imagedestroy($im3);

$file = '/tmp/outlet_parking.png';
imagepng($im4, $file);
imagedestroy($im4);
/*
header('Content-Type: image/png');
echo file_get_contents($file);
*/

$url = 'https://api.cloudmersive.com/ocr/image/toText';

$post_data = ['imageFile' => new CURLFile($file)];

$options = [
  CURLOPT_POST => TRUE,
  CURLOPT_HTTPHEADER => ['Apikey: ' . getenv('CLOUDMERSIVE_API_KEY'),
                         'Accept: application/json'],
  CURLOPT_POSTFIELDS => $post_data,
  CURLOPT_TIMEOUT => 20,
  ];

$res = $mu->get_contents($url, $options);

$data = json_decode($res);
error_log($pid . ' $data : ' . print_r($data, TRUE));

file_put_contents('/tmp/parse.txt', str_replace('0/0', '%', trim($data->TextResult)));

error_log("${pid} FINISH");
?>
