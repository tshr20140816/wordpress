<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s'));

$mu = new MyUtils();

$urls = [
    $mu->get_env('URL_RIVER_1'),
    $mu->get_env('URL_RIVER_2'),
    ];

$list_ch = [];
$mh = curl_multi_init();

foreach ($urls as $url) {
    $ch = curl_init();
    $options = [CURLOPT_URL => $url,
                CURLOPT_USERAGENT => getenv('USER_AGENT'),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_FOLLOWLOCATION => 1,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_SSL_FALSESTART => true,
    ];
    curl_setopt_array($ch, $options);
    curl_multi_add_handle($mh, $ch);
    $list_ch[$url] = $ch;
}

$active = null;
$rc = curl_multi_exec($mh, $active);

while ($active && $rc == CURLM_OK) {
    if (curl_multi_select($mh) == -1) {
        usleep(1);
    }
    $rc = curl_multi_exec($mh, $active);
}

foreach ($urls as $url) {
    $ch = $list_ch[$url];
    $results = curl_getinfo($ch);
    $res = curl_multi_getcontent($ch);
    curl_multi_remove_handle($mh, $ch);
    curl_close($ch);
    error_log(print_r($results, true));
}
curl_multi_close($mh);
