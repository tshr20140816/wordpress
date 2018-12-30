<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s'));

$mu = new MyUtils();

$urls = [
    $mu->get_env('URL_AMEDAS'),
    $mu->get_env('URL_KASA_SHISU_YAHOO'),
    $mu->get_env('URL_KASA_SHISU'),
    $mu->get_env('URL_TAIKAN_SHISU'),
    $mu->get_env('URL_RIVER_1'),
    $mu->get_env('URL_RIVER_2'),
    'https://tenki.jp/week/' . $mu->get_env('LOCATION_NUMBER') . '/',
    ];

for ($i = 1; $i < 5; $i++) {
    $urls[] = $mu->get_env('URL_PARKING_1') . '?park_id=' . $i . '&mode=pc';
}

$urls[] = 'https://map.yahooapis.jp/weather/V1/place?interval=5&output=json&appid=' . getenv('YAHOO_API_KEY')
    . '&coordinates=' . $mu->get_env('LONGITUDE') . ',' . $mu->get_env('LATITUDE');

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
    $res = curl_getinfo($ch);
    if ($res['http_code'] == 200) {
        // $results[] = curl_multi_getcontent($ch);
        $result = curl_multi_getcontent($ch);
        error_log(strlen($result) . ' ' . $url);
        $result = bzcompress($result, 9);
        error_log(strlen($result) . ' ' . $url);
        apcu_store($url, $results);
    }
    curl_multi_remove_handle($mh, $ch);
    curl_close($ch);
    // error_log(print_r($res, true));
}
curl_multi_close($mh);

error_log('--- NOTE ---');

foreach ($urls as $url) {
    if (apcu_exists($url) === true) {
        $res = bzdecompress(apcu_fetch($url));
        error_log(strlen($res) . ' ' . $url);
    }
}
