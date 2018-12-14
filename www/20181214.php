<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$list_holiday2 = get_holiday2($mu);

exit();

function get_holiday2($mu_) {

  $start_yyyy = date('Y', strtotime('+2 month'));
  $start_m = date('n', strtotime('+2 month'));
  $finish_yyyy = $start_yyyy + 2;
  // $finish_m = 12;

  $url = 'http://calendar-service.net/cal?start_year=' . $start_yyyy
    . '&start_mon=' . $start_m . '&end_year=' . $finish_yyyy . '&end_mon=12'
    . '&year_style=normal&month_style=numeric&wday_style=ja_full&format=csv&holiday_only=1&zero_padding=1';

  $res = $mu_->get_contents($url, NULL, TRUE);
  $res = mb_convert_encoding($res, 'UTF-8', 'EUC-JP');

  $tmp = explode("\n", $res);
  array_shift($tmp); // ヘッダ行削除
  array_pop($tmp); // フッタ行(空行)削除

  $list_holiday2 = [];
  for ($i = 0; $i < count($tmp); $i++) {
    $tmp1 = explode(',', $tmp[$i]);
    $timestamp = mktime(0, 0, 0, $tmp1[1], $tmp1[2], $tmp1[0]);
    $list_holiday2[$timestamp] = $tmp1[7];
  }
  error_log(getmypid() . ' [' . __METHOD__ . '] $list_holiday2 : ' . print_r($list_holiday2, TRUE));

  return $list_holiday2;
}
?>
