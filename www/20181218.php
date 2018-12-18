<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s'));

$mu = new MyUtils();

$url = 'http://www.motomachi-pa.jp/cgi/manku.pl?park_id=1&mode=pc';
$res = $mu->get_contents($url);

error_log(md5($res));

$url = 'http://www.motomachi-pa.jp/cgi/manku.pl?park_id=2&mode=pc';
$res = $mu->get_contents($url);

error_log(md5($res));

$url = 'http://www.motomachi-pa.jp/cgi/manku.pl?park_id=3&mode=pc';
$res = $mu->get_contents($url);

error_log(md5($res));

$url = 'http://www.motomachi-pa.jp/cgi/manku.pl?park_id=4&mode=pc';
$res = $mu->get_contents($url);

error_log(md5($res));

$url = 'http://the-outlets-hiroshima.com/static/detail/car';
$res = $mu->get_contents($url);
$rc = preg_match('/<p id="parkingnow"><img src="(.+?)"/s', $res, $matches);
$res = $mu->get_contents($matches[1]);

error_log(md5($res));
?>
