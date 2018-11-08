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

error_log(print_r($list_tmp, TRUE));

for ($i = 1; $i < count($list_tmp) - 1; $i++) {
  $tmp = explode(',', $list_tmp[$i]);
  $timestamp = strtotime(trim($tmp[1], '"'));
  if (date('Ymd') >= date('Ymd', $timestamp)) {
    continue;
  }
  error_log(print_r($tmp, TRUE));
  $rc = preg_match('/\d+:\d+:\d\d/', $tmp[2]);
  if ($rc == 1) {
    $tmp1 = substr($tmp[2], 0, strlen($tmp[2]) - 3);
  } else {
    $tmp1 = $tmp[2];
  }
  $tmp1 = substr($tmp[1], 5) . ' ' . $tmp1 . ' ' . $tmp[0] . ' ' . $tmp[6];
  error_log($tmp1);
}

?>
