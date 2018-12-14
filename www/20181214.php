<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$list_holiday2 = get_holiday2($mu);

$list_add_task = [];

// heroku-buildpack-php

$file_name_current = '/tmp/current_version';
$file_name_latest = '/tmp/latest_version';

if (file_exists($file_name_current) && file_exists($file_name_latest)) {
  $current_version = trim(trim(file_get_contents($file_name_current), '"'));
  $latest_version = trim(trim(file_get_contents($file_name_latest), '"'));
  error_log($pid . ' heroku-buildpack-php current : ' . $current_version);
  error_log($pid . ' heroku-buildpack-php latest : ' . $latest_version);
  if ($current_version != $latest_version) {
    $list_add_task[date('Ymd')] = '{"title":"heroku-buildpack-php : update ' . $latest_version
      . '","duedate":"' . mktime(0, 0, 0, 1, 1, 2018)
      . '","context":' . $list_context_id[date('w', mktime(0, 0, 0, 1, 1, 2018))] . '}';
  }
}

error_log(print_r($list_add_task, TRUE));

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
