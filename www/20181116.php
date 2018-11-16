<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$url = 'https://map.yahooapis.jp/weather/V1/place?appid=' . getenv('YAHOO_API_KEY') . '&coordinates=139.732293,35.663613';

$res = $mu->get_contents($url);

error_log($res);

?>
