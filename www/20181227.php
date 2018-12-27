<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s'));

$mu = new MyUtils();

$timeout = 5;

$mh = curl_multi_init();

$urls = [$mu->get_env('URL_KASA_SHISU_YAHOO'), $mu->get_env('URL_WEATHER_WARN')];

error_log(print_r($urls, true));

$ch = [];
foreach ($urls as $url) {
    error_log('POINT 100');
    $ch[$url] = curl_init();
    curl_setopt_array($ch[$url], array(
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_CONNECTTIMEOUT => $timeout,
        CURLOPT_USERAGENT => getenv('USER_AGENT'),
    ));
    curl_multi_add_handle($mh, $ch[$url]);
    error_log('POINT 110');
}

do {
    error_log('POINT 120');
    $stat = curl_multi_exec($mh, $running); //multiリクエストスタート
    error_log('POINT 130');
} while ($stat === CURLM_CALL_MULTI_PERFORM);
if ( ! $running || $stat !== CURLM_OK) {
    error_log('POINT 140');
    exit();
}

error_log('POINT 150');

$active = null;
do {
    $mrc = curl_multi_exec($mh, $active);
} while ($mrc == CURLM_CALL_MULTI_PERFORM);

while ($active && $mrc == CURLM_OK) {
    if (curl_multi_select($mh) == -1) {
        usleep(1);
    }

    do {
        $mrc = curl_multi_exec($mh, $active);
    } while ($mrc == CURLM_CALL_MULTI_PERFORM);
    // error_log('POINT 160');
}

foreach ($urls as $url) {
    $results[$url] = curl_getinfo($ch[$url]);
    curl_multi_remove_handle($mh, $ch[$url]);
    curl_close($ch[$url]);
}
error_log(print_r($results, true)):

curl_multi_close($mh);

error_log(getmypid() . ' FINISH');
