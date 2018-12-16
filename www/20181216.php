<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$url = 'http://the-outlets-hiroshima.com/static/detail/car';
$res = $mu->get_contents($url);

$rc = preg_match('/<p id="parkingnow"><img src="(.+?)"/s', $res, $matches);
// $res = $mu->get_contents($matches[1]);

$url = 'https://api.ocr.space/parse/imageurl?language=jpn&apikey=' . getenv('OCRSPACE_APIKEY') . '&url=' . $matches[1];
$url = 'https://api.ocr.space/parse/imageurl?apikey=' . getenv('OCRSPACE_APIKEY') . '&url=' . $matches[1];

$res = $mu->get_contents($url);

$data = json_decode($res);
error_log(print_r($data, TRUE));
?>
