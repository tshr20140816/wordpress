<?php

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi}");

$list_yobi = array('日', '月', '火', '水', '木', '金', '土');

$yyyy_limit = date('Y', strtotime('+3 years'));
error_log("${pid} YEAR LIMIT : ${yyyy_limit}");

$list_base = [];
for ($i = 0; $i < 1096 - 80; $i++) {
  $timestamp = strtotime('+' . ($i + 80) . ' days');
  $yyyy = date('Y', $timestamp);
  if ($yyyy_limit == $yyyy) {
    break;
  }
  $d = date('j', $timestamp);
  if ($d == 1 || $d == 11 || $d == 21) {
    //error_log($list_yobi[date('w', $timestamp)] . '曜日 ' . date('m/d', $timestamp));
    $list_base['##### ' . $list_yobi[date('w', $timestamp)] . '曜日 ' . date('m/d', $timestamp) . ' #####'] = date('Ymd', $timestamp);
  }
}
error_log(print_r($list_base, TRUE));

error_log("${pid} FINISH");

exit();

?>
