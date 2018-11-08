<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi}");

$mu = new MyUtils();

$url = 'http://soccer.phew.homeip.net/download/schedule/data/SJIS_all_hirosima.csv';
$res = $mu->get_contents($url);
$res = mb_convert_encoding($res, 'UTF-8', 'SJIS');

error_log($res);

$list_tmp = explode("\n", $res);

error_log(print_r($list_tmp, TRUE));

?>
