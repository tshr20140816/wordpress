<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$url = 'http://www.jma.go.jp/jp/amedas_h/today-44132.html';

$res = $mu->get_contents($url);

// error_log($res);

$tmp = explode('<td class="time left">時</td>', $res);
$tmp = explode('</table>', $tmp[1]);

// error_log($tmp[0]);

$rc = preg_match_all('/<tr>(.*?)<td(.*?)>(.+?)<\/td>(.*?)<td(.*?)>(.+?)<\/td><td(.*?)>(.+?)<\/td><td(.*?)>(.+?)<\/td><td(.*?)>(.+?)<\/td><td(.*?)>(.+?)<\/td><td(.*?)>(.+?)<\/td><td(.*?)>(.+?)<\/td>(.+?)<\/tr>/s', $tmp[0], $matches, PREG_SET_ORDER);

error_log(print_r($matches, TRUE));

//$hour = $matches

?>
