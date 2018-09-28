<?php

$res = file_get_contents('https://tenki.jp/week/' . getenv('LOCATION_NUMBER') . '/');

$tmp = explode(getenv('POINT_NAME'), $res);
$tmp = explode('<td class="forecast-wrap">', $tmp[1]);

for ($i = 1; $i < 11; $i++) {
  //error_log(str_replace(' ', '', trim(strip_tags($tmp[$i]))));
  $list = explode("\n", str_replace(' ', '', trim(strip_tags($tmp[$i]))));
  $tmp2 = $list[0];
  $tmp2 = str_replace('晴', '☼', $tmp2);
  $tmp2 = str_replace('曇', '☁', $tmp2);
  $tmp2 = str_replace('雨', '☂', $tmp2);
  $tmp2 = str_replace('のち', '/', $tmp2);
  $tmp2 = str_replace('時々', '|', $tmp2);
  $tmp2 = str_replace('一時', '|', $tmp2);
  error_log($tmp2 . ' ' . $list[1] . ' ' . $list[2]);
}

?>
