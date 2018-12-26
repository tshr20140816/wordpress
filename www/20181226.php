<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s'));

$mu = new MyUtils();

$url = getenv('URL_RIVER_1');

$res = $mu->get_contents($url);

$res = mb_convert_encoding($res, 'UTF-8', 'SJIS');
// error_log($res);

$tmp = explode('<hr/>', $res)[2];
$tmp = trim(str_replace('  ', ' ', strip_tags(str_replace('&nbsp;', '', $tmp))));
$tmp = str_replace('の更新情報', '', $tmp);
$tmp = str_replace('単位：m ■', '', $tmp);
$tmp = str_replace('(自)', ' ', $tmp) . 'm';
error_log($tmp);

$url = getenv('URL_RIVER_2');

$res = $mu->get_contents($url);
// error_log($res);

$tmp = explode('<div id="hyou" style="width:278px; height:390px; overflow-y:auto;">', $res)[1];
$tmp = explode('</table>', $tmp)[0];
// $tmp = explode('</tr>', $tmp);
$rc = preg_match('/.+<tr.+?>.+?<td.+?>(.+?)<\/td>.+?<td.+?>(.+?)</s', $tmp, $matches);
// error_log(print_r($matches, true));
error_log(trim($matches[1]) . ' 武庫川 生瀬 ' . trim($matches[2]) . 'm');
