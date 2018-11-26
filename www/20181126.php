<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$res = $mu->get_contents('http://www.jma.go.jp/jp/amedas_h/today-' . getenv('AMEDAS') . '.html');

$tmp = explode('">時刻</td>', $res);
$tmp = explode('</table>', $tmp[1]);

$rc = preg_match_all('/<tr>(.*?)<td(.*?)>(.+?)<\/td>(.*?)' . str_repeat('<td(.*?)>(.+?)<\/td>', 8) . '(.+?)<\/tr>/s'
                     , $tmp[0], $matches, PREG_SET_ORDER);
                     
error_log(print_r($matches, TRUE));

?>
