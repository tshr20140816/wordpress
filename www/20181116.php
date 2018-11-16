<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$url = 'https://map.yahooapis.jp/weather/V1/place?interval=5&output=json&appid=' . getenv('YAHOO_API_KEY')
  . '&coordinates=139.732293,35.663613';

$res = $mu->get_contents($url);

error_log($res);

$data = json_decode($res, TRUE);

error_log(print_r($data, TRUE));
?>
