<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s'));

$mu = new MyUtils();

$timeout = 10;

$mh = curl_multi_init();

$urls = [$mu->get_env('URL_KASA_SHISU_YAHOO'), $mu->get_env('URL_WEATHER_WARN')];

foreach ($urls as $url) {
    $ch = curl_init();
    curl_setopt_array($ch, [CURLOPT_URL => $url,
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_TIMEOUT => $timeout,
                            CURLOPT_CONNECTTIMEOUT => $timeout]);
    curl_multi_add_handle($mh, $ch);
}

$stat = curl_multi_exec($mh, $running);

do switch (curl_multi_select($mh, $timeout)) {
    case -1:
        break;
    case 0:
        break;
    default:
        do {
            $stat = curl_multi_exec($mh, $running);
        } while ($stat === CURLM_CALL_MULTI_PERFORM);
        
        do if ($raised = curl_multi_info_read($mh, $remains)) {
            $info = curl_getinfo($raised['handle']);
            $response = curl_multi_getcontent($raised['handle']);
            if ($response === false) {
                error_log('ERROR');
            } else {
                error_log($response);
            }
            curl_multi_remove_handle($mh, $raised['handle']);
            curl_close($raised['handle']);
        } while ($remains);
} while ($running);

curl_multi_close($mh);

error_log(getmypid() . ' FINISH');
