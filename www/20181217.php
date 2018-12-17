<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$url = 'https://weather.yahoo.co.jp/weather/jp/27/6200.html';
$res = $mu->get_contents($url);

// <!--指数情報-->
// <!--/指数情報-->

$tmp = explode('<!--指数情報-->', $res, 2);
$tmp = explode('<!--/指数情報-->', $tmp[1], 2);
error_log($tmp[0]);

?>
