<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s'));

$mu = new MyUtils();

/*
$list = make_curl_multi($mu->get_env('URL_KASA_SHISU_YAHOO'));

error_log(print_r($list, true));

func_sample($mu, $list);
    
exit();
*/

$timeout = 5;

$mh = curl_multi_init();

$url = $mu->get_env('URL_KASA_SHISU_YAHOO');

$ch = curl_init();
curl_setopt_array($ch, array(
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => $timeout,
    CURLOPT_CONNECTTIMEOUT => $timeout,
    CURLOPT_USERAGENT => getenv('USER_AGENT'),
));
curl_multi_add_handle($mh, $ch);

$active = null;
do {
    $mrc = curl_multi_exec($mh, $active);
} while ($mrc == CURLM_CALL_MULTI_PERFORM);

func_sample2($active, $mrc, $mh, $ch) ;

function func_sample2($active, $mrc, $mh, $ch) {
while ($active && $mrc == CURLM_OK) {
    if (curl_multi_select($mh) == -1) {
        usleep(1);
    }

    do {
        $mrc = curl_multi_exec($mh, $active);
    } while ($mrc == CURLM_CALL_MULTI_PERFORM);
}

$results = curl_getinfo($ch);
$res = curl_multi_getcontent($ch);
curl_multi_remove_handle($mh, $ch);
curl_close($ch);

error_log(print_r($results, true));

curl_multi_close($mh);
}

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
                CURLOPT_TIMEOUT => 10,
                CURLOPT_CONNECTTIMEOUT => 10,
    ];
    curl_setopt_array($list[$url_]['channel'], $options);
    curl_multi_add_handle($list[$url_]['multi_handle'], $list[$url_]['channel']);
    $list[$url_]['active'] = null;
    do {
        $list[$url_]['rc'] = curl_multi_exec($list[$url_]['multi_handle'], $list[$url_]['active']);
    } while ($list[$url_]['rc'] == CURLM_CALL_MULTI_PERFORM);
    error_log(getmypid() . ' curl_multi_exec : ' . $list[$url_]['rc']);
    
    return $list;
}

function func_sample($mu_, $list_) {
    
    $url = $mu_->get_env('URL_KASA_SHISU_YAHOO');
    
    error_log($url);
    error_log(print_r($list_, true));
    
    $mh = $list_[$url]['multi_handle'];
    $ch = $list_[$url]['channel'];
    $rc = $list_[$url]['rc'];
    $active = $list_[$url]['rc'];
    
    error_log('POINT 010');
    while ($active && $rc == CURLM_OK) {
        error_log('POINT 100');
        if (curl_multi_select($mh) == -1) {
            usleep(1);
        }

        do {
            $rc = curl_multi_exec($mh, $active);
        } while ($rc == CURLM_CALL_MULTI_PERFORM);
        error_log('POINT 200');
    }
    error_log('POINT 300');
    
    $results = curl_getinfo($ch);
    $res = curl_multi_getcontent($ch);
    
    error_log(print_r($results, true));
    error_log(strlen($res));
    
    curl_multi_remove_handle($mh, $ch);
    curl_close($ch);
    curl_multi_close($mh);
}
