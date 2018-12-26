<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s'));

$mu = new MyUtils();

$url = 'http://www.kasen-bousai.pref.hiroshima.lg.jp/rivercontents/p10202/10/31_1.html';

$res = $mu->get_contents($url);

error_log($res);
