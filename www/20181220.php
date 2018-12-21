<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();

$mu = new MyUtils();

$access_token = $mu->get_access_token();
$url = 'https://api.toodledo.com/3/tasks/get.php?comp=0&fields=tag,duedate,context,star,folder&access_token=' . $access_token;
$res = $mu->get_contents($url);

error_log($pid . ' TASKS (GZIP BASE64) : ' . strlen(gzencode(base64_encode($res), 9)));
error_log($pid . ' TASKS (GZIP) : ' . strlen(gzencode($res, 9)));

?>
