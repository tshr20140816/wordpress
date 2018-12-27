<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s'));

$mu = new MyUtils();

$list = make_curl_multi($mu->get_env('URL_KASA_SHISU_YAHOO'));

error_log(print_r($list, true));

exit();

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

$results = [];
foreach ($urls as $url) {
    $results[$url] = curl_getinfo($ch[$url]);
    $results[$url]["content"] = curl_multi_getcontent($ch[$url]);
    curl_multi_remove_handle($mh, $ch[$url]);
    curl_close($ch[$url]);
}
error_log(print_r($results, true));

curl_multi_close($mh);

error_log(getmypid() . ' FINISH');

function make_curl_multi($url_) {
    $list[$url_]['multi_handle'] = curl_multi_init();
    $list[$url_]['channel'] = curl_init();

    $options = [CURLOPT_URL => $url_,
                CURLOPT_USERAGENT => getenv('USER_AGENT'),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_FOLLOWLOCATION => 1,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_SSL_FALSESTART => true,
    ];
    curl_setopt_array($list[$url_]['channel'], $options);
    curl_multi_add_handle($list[$url_]['multi_handle'], $list[$url_]['channel']);
    do {
        $list[$url_]['rc'] = curl_multi_exec($list[$url_]['multi_handle'], $running);
    } while ($list[$url_]['rc'] == CURLM_CALL_MULTI_PERFORM);
    error_log(getmypid() . ' curl_multi_exec : ' . $list[$url_]['rc']);
    
    return $list;
}
