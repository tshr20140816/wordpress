<?php

$url = 'http://www.cf.city.hiroshima.jp/saeki-cs/sche6_park/sche6.cgi?year=2018&mon=11';

$res = file_get_contents($url);

$tmp = explode('<col span=1 align=right>', $res);
$tmp = explode('</table>', $tmp[1]);

$rc = preg_match('/<tr .+?<b>(.+?)<.*>(.*)<\/td><\/tr>/', $tmp[0], $matches);

error_log( print_r($matches, TRUE));
?>
