<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$url = 'https://weather.yahoo.co.jp/weather/jp/27/6200.html';
$res = $mu->get_contents($url);

// <!--指数情報-->
// <!--/指数情報-->

$rc = preg_match_all('/<!--指数情報-->(.+?)<!--\/指数情報-->/s', $res, $matches, PREG_SET_ORDER);

error_log(print_r($matches, TRUE));

?>
