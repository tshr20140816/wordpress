<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
$time_start = time();
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s', $time_start));

$rc = apcu_clear_cache();

$mu = new MyUtils();

$sub_address = $mu->get_env('SUB_ADDRESS');
//for ($i = 0; $i < 12; $i++) {
for ($i = 11; $i > -1; $i--) {
    $url = 'https://feed43.com/' . $sub_address . ($i * 5 + 11) . '-' . ($i * 5 + 15) . '.xml';
    $res = $mu->get_contents($url);
}

$time_finish = time();
error_log("${pid} FINISH " . date('s', $time_finish - $time_start) . 's');
exit();
