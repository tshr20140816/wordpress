<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s'));

$mu = new MyUtils();

$url = 'http://i.river.go.jp/_-p02-_/p/ktm1201010/?mtm=0&swd=&rvr=87712001&prf=3401';

$res = $mu->get_contents($url);

$res = mb_convert_encoding($res, 'UTF-8', 'SJIS');

// error_log($res);

$tmp = explode('<hr/>', $res)[2];

// error_log($tmp);

$tmp = trim(str_replace('  ', ' ', strip_tags(str_replace('&nbsp;', '', $tmp))));
$tmp = str_replace('の更新情報', '', $tmp);
$tmp = str_replace('単位：m ■', '', $tmp);
$tmp = str_replace('(自)', ' ', $tmp) . 'm';
error_log($tmp);

$url = 'http://www.river.go.jp/kawabou/ipSuiiKobetu.do?obsrvId=0716900400013&gamenId=01-1003&stgGrpKind=survForeKjExpl&fldCtlParty=no&fvrt=yes&timeType=10';

$res = $mu->get_contents($url);

error_log($tmp);
