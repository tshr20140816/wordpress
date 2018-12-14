<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$url = 'http://the-outlets-hiroshima.com/static/detail/car';

$res = $mu->get_contents($url);

error_log($res);

//<p id="parkingnow">

$rc = preg_match('/<p id="parkingnow"><img src="(.+?)"/s', $res, $matches);

error_log(print_r($matches, TRUE));
?>
