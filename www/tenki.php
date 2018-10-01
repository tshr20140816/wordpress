<?php

$res = file_get_contents('https://tenki.jp/week/' . getenv('LOCATION_NUMBER') . '/');

$rc = preg_match('/announce_datetime:(\d+-\d+-\d+)/', $res, $matches);

error_log($matches[0]);
error_log($matches[1]);

$dt = $matches[1];

$tmp = explode(getenv('POINT_NAME'), $res);
$tmp = explode('<td class="forecast-wrap">', $tmp[1]);

for ($i = 0; $i < 10; $i++) {
  // error_log(date('m/d', strtotime($dt . ' +' . $i . " day")));
  $list = explode("\n", str_replace(' ', '', trim(strip_tags($tmp[$i + 1]))));
  $tmp2 = $list[0];
  $tmp2 = str_replace('晴', '☼', $tmp2);
  $tmp2 = str_replace('曇', '☁', $tmp2);
  $tmp2 = str_replace('雨', '☂', $tmp2);
  $tmp2 = str_replace('のち', '/', $tmp2);
  $tmp2 = str_replace('時々', '|', $tmp2);
  $tmp2 = str_replace('一時', '|', $tmp2);
  error_log('+++++ ' . date('m/d', strtotime($dt . ' +' . $i . ' day')) . ' ' . $tmp2 . ' ' . $list[1] . ' ' . $list[2]. ' +++++');
}

?>
