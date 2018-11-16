<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$url = 'http://www.jma.go.jp/jp/amedas_h/today-44132.html';

$res = $mu->get_contents($url);

error_log($res);

?>
