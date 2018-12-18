<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s'));

$mu = new MyUtils();

$url = 'http://www.motomachi-pa.jp/cgi/manku.pl?park_id=3&mode=pc';
$res = $mu->get_contents($url);

$im1 = imagecreatefromstring($res);
imagefilter($im1, IMG_FILTER_GRAYSCALE);

$im3 = imagecreatetruecolor(imagesx($im1) / 4, imagesy($im1) / 4);
imagecopyresampled($im3, $im1, 0, 0, 0, 0, imagesx($im1) / 4, imagesy($im1) / 4, imagesx($im1), imagesy($im1));
imagedestroy($im1);

$im2 = imagecreatetruecolor(imagesx($im3), imagesy($im3));
for($x = 0; $x < imagesx($im3); ++$x)
{
  for($y = 0; $y < imagesy($im3); ++$y)
  {
    $index = imagecolorat($im3, $x, $y);
    $rgb = imagecolorsforindex($im3, $index);
    // error_log(print_r($rgb, TRUE));
    // $color = imagecolorallocate($im2, 255 - $rgb['red'], 255 - $rgb['green'], 255 - $rgb['blue']);
    if ($rgb['red'] > 230 && $rgb['green'] > 230 && $rgb['blue'] > 230) {
      $color = imagecolorallocate($im2, 0, 0, 0);
    } else {
      $color = imagecolorallocate($im2, 255, 255, 255);
    }

    imagesetpixel($im2, $x, $y, $color);
  }
}

$file = '/tmp/motomachi_parking_information.png';

//imagepng($im2, $file);
header('Content-type: image/png');
imagepng($im2);
imagedestroy($im1);
imagedestroy($im2);
imagedestroy($im3);

exit();

$url = 'https://api.cloudmersive.com/ocr/image/toText';

$post_data = ['imageFile' => new CURLFile($file)];

$options = [
  CURLOPT_POST => TRUE,
  CURLOPT_HTTPHEADER => ['Apikey: ' . getenv('CLOUDMERSIVE_API_KEY'),
                         'language: JPN',
                         'Accept: application/json'],
  CURLOPT_POSTFIELDS => $post_data,
  CURLOPT_TIMEOUT => 20,
  ];

$res = $mu->get_contents($url, $options);

$data = json_decode($res);
error_log($pid . ' $data : ' . print_r($data, TRUE));

?>
