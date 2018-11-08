<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi}");

$mu = new MyUtils();

$url = 'http://soccer.phew.homeip.net/download/schedule/data/SJIS_all_hirosima.csv';
$res = $mu->get_contents($url);
$res = mb_convert_encoding($res, 'UTF-8', 'SJIS');

error_log($res);

$list_tmp = explode("\n", $res);

// error_log(print_r($list_tmp, TRUE));

$list_soccer = [];
for ($i = 1; $i < count($list_tmp) - 1; $i++) {
  $tmp = explode(',', $list_tmp[$i]);
  $timestamp = strtotime(trim($tmp[1], '"'));
  if (date('Ymd') >= date('Ymd', $timestamp)) {
    continue;
  }
  // error_log(print_r($tmp, TRUE));
  $tmp1 = trim($tmp[2], '"');
  $rc = preg_match('/\d+:\d+:\d\d/', $tmp1);
  if ($rc == 1) {
    $tmp1 = substr($tmp1, 0, strlen($tmp1) - 3);
  }
  $tmp1 = substr(trim($tmp[1], '"'), 5) . ' ' . $tmp1 . ' ' . trim($tmp[0], '"') . ' ' . trim($tmp[6], '"');
  // error_log($tmp1);
  $list_soccer[date('Ymd', $timestamp)] = $tmp1;
}
error_log(print_r($list_soccer, TRUE));

?>
