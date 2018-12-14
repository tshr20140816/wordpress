<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$list_holiday2 = get_holiday2($mu);

exit();

function get_holiday2($mu_) {

  $list_holiday2 = [];
  for ($j = 0; $j < 3; $j++) {
    $yyyy = date('Y', strtotime('+' . $j . ' years'));

    $url = 'http://calendar-service.net/cal?start_year=' . $yyyy
      . '&start_mon=1&end_year=' . $yyyy . '&end_mon=12'
      . '&year_style=normal&month_style=numeric&wday_style=ja_full&format=csv&holiday_only=1&zero_padding=1';

    $res = $mu_->get_contents($url, NULL, TRUE);
    $res = mb_convert_encoding($res, 'UTF-8', 'EUC-JP');

    $tmp = explode("\n", $res);
    array_shift($tmp); // ヘッダ行削除
    array_pop($tmp); // フッタ行(空行)削除

    for ($i = 0; $i < count($tmp); $i++) {
      $tmp1 = explode(',', $tmp[$i]);
      $timestamp = mktime(0, 0, 0, $tmp1[1], $tmp1[2], $tmp1[0]);
      if (date('Ymd', $timestamp) < date('Ymd', strtotime('+100 days'))) {
        continue;
      }

      $yyyy = $mu_->to_small_size($tmp1[0]);
      $list_holiday2[date('Ymd', $timestamp)] = '### ' . $tmp1[5] . ' ' . $tmp1[1] . '/' . $tmp1[2] . ' ★' . $tmp1[7] . '★ ### ' . $yyyy;
    }
  }
  error_log(getmypid() . ' [' . __METHOD__ . '] $list_holiday2 : ' . print_r($list_holiday2, TRUE));

  return $list_holiday2;
}
?>
