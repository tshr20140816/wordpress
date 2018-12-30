<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s'));

$mu = new MyUtils();



$mh = curl_multi_init();
for ($urls as $url) {
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
    if (is_null($options_) == false) {
        curl_setopt_array($ch, $options_);
    }
    curl_multi_add_handle($mh, $ch);
}
