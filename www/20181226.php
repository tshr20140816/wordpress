<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s'));

$mu = new MyUtils();

$y = 2018;
$m = 12;
$d = 31;

$url = 'https://keisan.casio.jp/exec/system/1186108192';

$post_data = ['lang' => '', 'charset' => 'utf-8', 'var_c1' => '0', 'var_Y' => $y, 'var_M' => $m, 'var_D' => $d,];

$res = $mu->get_contents($url, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($post_data),]);

error_log($res);

$url = "http://api.sekido.info/qreki?output=json&year=${y}&month=${m}&day=${d}";

$res = $mu->get_contents($url);

error_log(print_r(json_decode($res, true), true));

$url = "https://dateinfoapi.appspot.com/v1?date=${y}-${m}-${d}";

$res = $mu->get_contents($url);

error_log(print_r(json_decode($res, true), true));

$url = 'http://koyomi.zing2.org/api/';

$post_data = ['mode' => 'd', 'cnt' => '1', 'targetyyyy' => $y, 'targetmm' => $m, 'targetdd' => $d,];

$res = $mu->get_contents($url, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($post_data),]);

error_log(print_r(json_decode($res, true), true));