<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$url = 'http://the-outlets-hiroshima.com/static/detail/car';
$res = $mu->get_contents($url);

$rc = preg_match('/<p id="parkingnow"><img src="(.+?)"/s', $res, $matches);
// $res = $mu->get_contents($matches[1]);

$url = 'https://api.ocr.space/parse/imageurl?language=jpn&apikey=' . getenv('OCRSPACE_APIKEY') . '&url=' . $matches[1];
$url = 'https://api.ocr.space/parse/imageurl';

$post_data = ['url' => $matches[1]];

$options = [
  CURLOPT_POST => TRUE,
  CURLOPT_HTTPHEADER => ['apiKey: ' . getenv('OCRSPACE_APIKEY')],
  CURLOPT_POSTFIELDS => http_build_query($post_data),
  ];

$res = $mu->get_contents($url, $options);

$data = json_decode($res);
error_log(print_r($data, TRUE));

/*
$res = $mu->get_contents($matches[1]);

$json = ['requests' => [['image' => ['content' => base64_encode($res)],
                         'features' => [['type' => 'TEXT_DETECTION',
                                         'maxResults' => 10]]]]];

$url = 'https://vision.googleapis.com/v1/images:annotate?key=' . getenv('GOOGLE_API_KEY');
$options = [
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
  CURLOPT_POSTFIELDS => json_encode($json),
  CURLOPT_REFERER => 'https://' . getenv('HEROKU_APP_NAME') . '.herokuapp.com/',
  ];
$res = $mu->get_contents($url, $options);

$data = json_decode($res);
error_log(print_r($data, TRUE));
*/
?>
