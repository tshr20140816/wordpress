<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$url = 'https://map.yahooapis.jp/search/local/V1/localSearch?output=json&appid=' . getenv('YAHOO_API_KEY')
  . '&dist=20&lon=' . getenv('LONGITUDE') . '&lat=' . getenv('LATITUDE');
$res = $mu->get_contents($url);
$data = json_decode($res, TRUE);
error_log(getmypid() . ' $data : ' . print_r($data, TRUE));

?>
