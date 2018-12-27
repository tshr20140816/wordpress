<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s'));

$mu = new MyUtils();

$url = $mu->get_env('URL_KASA_SHISU_YAHOO');

$list = make_curl_multi($url);

func_sample($mu, $list);

error_log(getmypid() . ' FINISH');

function make_curl_multi($url_)
{
    $mh = curl_multi_init();

    $ch = curl_init();
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
    curl_setopt_array($ch, $options);
    curl_multi_add_handle($mh, $ch);

    $active = null;
    do {
        $rc = curl_multi_exec($mh, $active);
    } while ($rc == CURLM_CALL_MULTI_PERFORM);
    
    $list[$url_]['multi_handle'] = $mh;
    $list[$url_]['channel'] = $ch;
    $list[$url_]['rc'] = $rc;
    $list[$url_]['active'] = $active;
    
    return $list;
}

function func_sample($mu_, $list_)
{
    error_log(__METHOD__);
    
    $url = $mu_->get_env('URL_KASA_SHISU_YAHOO');
    
    $active = $list_[$url]['active'];
    $rc = $list_[$url]['rc'];
    $ch = $list_[$url]['channel'];
    $mh = $list_[$url]['multi_handle'];
    
    while ($active && $rc == CURLM_OK) {
        if (curl_multi_select($mh) == -1) {
            usleep(1);
        }

        do {
            $rc = curl_multi_exec($mh, $active);
        } while ($rc == CURLM_CALL_MULTI_PERFORM);
    }

    $results = curl_getinfo($ch);
    $res = curl_multi_getcontent($ch);
    curl_multi_remove_handle($mh, $ch);
    curl_close($ch);

    error_log(print_r($results, true));

    curl_multi_close($mh);
}
