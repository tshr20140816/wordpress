<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s'));

$mu = new MyUtils();

foreach ([getenv('URL_RIVER_1'), getenv('URL_RIVER_2')] as $url) {
    $res = $mu->get_contents($url);

    $rc = preg_match('/観測所：(.+?)\(/s', $res, $matches);
    $point_name = $matches[1];

    $rc = preg_match('/雨量観測所<\/th>.+?<td.+?>.+?<td.+?>(.+?)</s', $res, $matches);
    $river_name = trim($matches[1]);

    $tmp = explode('<div id="hyou" style="width:278px; height:390px; overflow-y:auto;">', $res)[1];
    $tmp = explode('</table>', $tmp)[0];
    $rc = preg_match('/.+<tr.+?>.+?<td.+?>(.+?)<\/td>.+?<td.+?>(.+?)</s', $tmp, $matches);

    error_log(trim($matches[1]) . " ${river_name} ${point_name} " . trim($matches[2]) . 'm');
}
