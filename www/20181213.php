<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$url = 'https://typhoon.yahoo.co.jp/weather/jp/warn/32/32201/';

$res = $mu->get_contents($url);

error_log($res);

/*
$url = 'https://typhoon.yahoo.co.jp/weather/jp/warn/5/5201/';

$res = $mu->get_contents($url);

error_log($res);
*/
?>
