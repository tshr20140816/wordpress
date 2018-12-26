<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s'));

$rc = apcu_clear_cache();

$mu = new MyUtils();

for ($i = 0; $i < 10; $i++) {
  $url = $mu->get_env('URL_OUTLET');
  error_log($url);
}
