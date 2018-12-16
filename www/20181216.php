<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$url = 'http://the-outlets-hiroshima.com/static/detail/car';
$res = $mu->get_contents($url);

$rc = preg_match('/<p id="parkingnow"><img src="(.+?)"/s', $res, $matches);
$res = $mu->get_contents($matches[1]);

$filePath = '/tmp/sample_image.jpg';
file_put_contents($filePath, $res);

// $url = 'http://www.ocrwebservice.com/restservices/processDocument?gettext=true';
$url = 'http://www.ocrwebservice.com/restservices/processDocument?language=english&gettext=true&getwords=true&newline=1';

// $username = getenv('OCRWEBSERVICE_USER');
// $license_code = getenv('OCRWEBSERVICE_LICENSE_CODE');
  
$fp = fopen($filePath, 'r');

$options = [
  CURLOPT_USERPWD => getenv('OCRWEBSERVICE_USER') . ':' . getenv('OCRWEBSERVICE_LICENSE_CODE'),
  CURLOPT_UPLOAD => TRUE,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_TIMEOUT => 200,
  CURLOPT_HEADER => FALSE,
  CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
  CURLOPT_INFILE => $fp,
  CURLOPT_INFILESIZE => filesize($filePath),
];

$res = $mu->get_contents($url, $options);

fclose($fp);

$data = json_decode($res);
error_log(print_r($data, TRUE));

error_log(print_r($data->OCRWords[0][2]->OCRWord, TRUE));
?>
