<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$url = 'https://map.yahooapis.jp/weather/V1/place?interval=5&output=json&appid=' . getenv('YAHOO_API_KEY')
  . '&coordinates=' . getenv('LONGITUDE') . ',' . getenv('LATITUDE');

$res = $mu->get_contents($url);

error_log($res);

$data = json_decode($res, TRUE);
$data = $data['Feature'][0]['Property']['WeatherList']['Weather'];

error_log(print_r($data, TRUE));

$list = [];
for ($i = 0; $i < count($data); $i++) {
  // error_log(print_r($data[$i], TRUE));
  $list[] = substr($data[$i]['Date'], 8) . ' ' . $data[$i]['Rainfall'];
}
error_log(print_r($list, TRUE));

error_log(date('H'));
?>
