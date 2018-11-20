<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$url = 'https://www.w-nexco.co.jp/traffic_info/construction/traffic.php?fdate='
  . date('Ymd', strtotime('+1 day'))
  . '&tdate='
  . date('Ymd', strtotime('+14 day'))
  . '&ak=1&ac=1&kisei%5B%5D=901&dirc%5B%5D=1&dirc%5B%5D=2&order=1&ronarrow=1&road%5B%5D=1011&road%5B%5D=1912&road%5B%5D=1020&road%5B%5D=225A&road%5B%5D=1201&road%5B%5D=1222&road%5B%5D=1231&road%5B%5D=234D&road%5B%5D=1232';

$res = $mu->get_contents($url);

error_log($res);

?>
