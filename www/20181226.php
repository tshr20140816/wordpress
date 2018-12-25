<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s'));

$mu = new MyUtils();

$url = 'https://keisan.casio.jp/exec/system/1186108192';

$post_data = ['lang' => '', 'charset' => 'utf-8', 'var_c1' => '0', 'var_y' => '2018', 'var_m' => '12', 'var_d' => '26',];

$res = $mu->get_contents($url
