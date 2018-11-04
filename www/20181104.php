<?php

$url = 'http://www.cf.city.hiroshima.jp/saeki-cs/sche6_park/sche6.cgi?year=2018&mon=11';

$res = file_get_contents($url);
$res = mb_convert_encoding($res, 'UTF-8', 'SJIS');

// error_log($res);

$tmp = explode('<col span=1 align=right>', $res);
$tmp = explode('</table>', $tmp[1]);

error_log($tmp[0]);
/*
<tr bgcolor="#fef0ef"><td width=60><font color="#c00000"><b>3</b> (土)</font></td><td>
<font color="#c00000">文化の日</font>　×××大混雑9:00～21:00</td></tr>
*/
$rc = preg_match_all('/<tr .+?<b>(.+?)<.*?<td>(.*?)<\/td><\/tr>/s', $tmp[0], $matches);

error_log(print_r($matches, TRUE));
?>
