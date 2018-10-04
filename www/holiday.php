<?php

// $url = 'http://calendar-service.net/cal?start_year=2018&start_mon=11&end_year=2020&end_mon=12&year_style=normal&month_style=numeric&wday_style=ja_full&format=csv&holiday_only=1&zero_padding=1';
$url = 'http://calendar-service.net/cal?start_year=2018&start_mon=11&end_year=2018&end_mon=12&year_style=normal&month_style=numeric&wday_style=ja_full&format=csv&holiday_only=1&zero_padding=1';

$res = file_get_contents($url);

$res = mb_convert_encoding($res, 'UTF-8', 'EUC-JP');

// error_log($res);

$tmp_list = explode("\n", $res);

for ($i = 1; $i < count($tmp_list); $i++) {
  error_log($tmp_list[$i]);
  $tmp = explode(',', $tmp_list[$i]);
  //error_log($tmp[0] . $tmp[1] . $tmp[2] . $tmp[5] . $tmp[7]);
  error_log('####+ ' . $tmp[7] . ' ' . $tmp[0] . '/' . $tmp[1] . '/' . $tmp[2] . ' +####');
}

?>
