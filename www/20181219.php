<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();
$url = 'http://the-outlets-hiroshima.com/static/detail/car';
$res = $mu->get_contents($url);
$rc = preg_match('/<p id="parkingnow"><img src="(.+?)"/s', $res, $matches);
$res = $mu->get_contents($matches[1]);

error_log($pid . ' NEW IMAGE (BASE64) LENGTH : ' . strlen(base64_encode($res)));
error_log($pid . ' NEW IMAGE (GZIP BASE64) LENGTH : ' . strlen(gzencode(base64_encode($res))));
error_log($pid . ' NEW IMAGE (GZIP 9 BASE64) LENGTH : ' . strlen(gzencode(base64_encode($res), 9)));

?>
