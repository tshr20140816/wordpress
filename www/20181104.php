<?php

$url = 'http://www.cf.city.hiroshima.jp/saeki-cs/sche6_park/sche6.cgi?year=2018&mon=11';

$res = file_get_contents($url);
$res = mb_convert_encoding($res, 'UTF-8', 'SJIS');

// error_log($res);

$tmp = explode('<col span=1 align=right>', $res);
$tmp = explode('</table>', $tmp[1]);

error_log($tmp[0]);

$rc = preg_match_all('/<tr .+?<b>(.+?)<.*?<td(.*?)<\/td><\/tr>/s', $tmp[0], $matches, PREG_SET_ORDER);

error_log(print_r($matches, TRUE));
?>
