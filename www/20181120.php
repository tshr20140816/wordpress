<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$url = 'https://www.w-nexco.co.jp/traffic_info/construction/traffic.php?fdate='
  . date('Ymd', strtotime('+1 day'))
  . '&tdate='
  . date('Ymd', strtotime('+14 day'))
  . '&ak=1&ac=1&kisei%5B%5D=901&dirc%5B%5D=1&dirc%5B%5D=2&order=2&ronarrow=1&road%5B%5D=1011&road%5B%5D=1912&road%5B%5D=1020&road%5B%5D=225A&road%5B%5D=1201&road%5B%5D=1222&road%5B%5D=1231&road%5B%5D=234D&road%5B%5D=1232&road%5B%5D=1260';

$res = $mu->get_contents($url);

//<table cellspacing="0" summary="" class="lb05">

$tmp = explode('<!--工事日程順-->', $res);
$tmp = explode('<table cellspacing="0" summary="" class="lb05">', $tmp[0]);
$tmp = explode('<th>備考</th>', $tmp[1]);
$tmp = $tmp[1];

//error_log($tmp);

$rc = preg_match_all('/<tr.*?>.*?<td.*?>(.+?)<\/td>.*?<td.*?>(.+?)<\/td>.*?<td.*?>(.+?)<\/td>(.+?)<\/tr>/s', $tmp, $matches, PREG_SET_ORDER);

error_log(print_r($matches, TRUE));


?>
