<?php

$res = file_get_contents('https://tenki.jp/week/' . getenv('LOCATION_NUMBER') . '/');

$tmp = explode(getenv('POINT_NAME'), $res);
$tmp = explode('<td class="forecast-wrap">', $tmp[1]);

for ($i = 1; $i < 11; $i++) {
  //error_log(str_replace(' ', '', trim(strip_tags($tmp[$i]))));
  $list = explode("\n", str_replace(' ', '', trim(strip_tags($tmp[$i]))));
  $tmp = $list[0];
  $tmp = str_replace('晴', '☼', $tmp);
  $tmp = str_replace('曇', '☁', $tmp);
  $tmp = str_replace('雨', '☂', $tmp);
  $tmp = str_replace('のち', '→', $tmp);
  $tmp = str_replace('時々', '|', $tmp);
  error_log($tmp . ' ' . $list[1] . ' ' . $list[2]);
}

?>
