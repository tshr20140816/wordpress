<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s'));

$mu = new MyUtils();

$url = 'http://i.river.go.jp/_-p02-_/p/ktm1201010/?mtm=0&swd=&rvr=87712001&prf=3401';

$res = $mu->get_contents($url);

$res = mb_convert_encoding($res, 'UTF-8', 'SJIS');

// error_log($res);

$tmp = explode('<hr/>', $res)[1];

error_log($tmp);
