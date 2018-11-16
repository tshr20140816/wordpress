<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$url = 'http://www.jma.go.jp/jp/amedas_h/today-44132.html';

$res = $mu->get_contents($url);

// error_log($res);

$tmp = explode('<td class="time left">時</td>', $res);
$tmp = explode('</table>', $tmp[1]);

// error_log($tmp[0]);

$rc = preg_match_all('/<tr>(.*?)<td(.*?)>(.+?)<\/td>(.*?)<td(.*?)>(.+?)<\/td><td(.*?)>(.+?)<\/td><td(.*?)>(.+?)<\/td><td(.*?)>(.+?)<\/td><td(.*?)>(.+?)<\/td><td(.*?)>(.+?)<\/td><td(.*?)>(.+?)<\/td>(.+?)<\/tr>/s', $tmp[0], $matches, PREG_SET_ORDER);

error_log(print_r($matches, TRUE));

for ($i = 0; $i < count($matches); $i++) {
  $hour = $matches[$i][3];
  $temp = $matches[$i][6];
  $rain = $matches[$i][8];
  $wind = $matches[$i][10] . $matches[$i][12];
  $humi = $matches[$i][16];
  $pres = $matches[$i][18];
  if ($temp == '&nbsp;') {
    continue;
  }
  error_log("${hour}時 気温:${temp}度 降水量:${rain}mm 風:${wind}m/s 湿度:${humi}% 気圧:${pres}hPa");
}

?>
