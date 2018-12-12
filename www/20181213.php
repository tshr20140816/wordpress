<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$url = 'https://typhoon.yahoo.co.jp/weather/jp/warn/32/32201/';

$res = $mu->get_contents($url);

$rc = preg_match_all('/<ul class="warnDetail_head_labels">(.+?)<\/ul>/s', $res, $matches, PREG_SET_ORDER);

error_log(print_r($matches, TRUE));

$url = 'https://typhoon.yahoo.co.jp/weather/jp/warn/5/5201/';

$res = $mu->get_contents($url);

$rc = preg_match_all('/<ul class="warnDetail_head_labels">(.+?)<\/ul>/s', $res, $matches, PREG_SET_ORDER);

error_log(print_r($matches, TRUE));

?>
