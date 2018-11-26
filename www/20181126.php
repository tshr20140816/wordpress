<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$res = $mu->get_contents('http://www.jma.go.jp/jp/amedas_h/today-' . getenv('AMEDAS') . '.html');

$tmp = explode('">時刻</td>', $res);
$tmp = explode('</table>', $tmp[1]);

$tmp1 = explode('</tr>', $tmp[0]);

$headers = explode('</td>', $tmp1[0]);
error_log(print_r($headers, TRUE));

for ($i = 0; $i < count($headers); $i++) {
  error_log(trim(strip_tags($headers[$i])));
  switch (trim(strip_tags($headers[$i]))) {
    case '気温':
      $index_temp = $i + 2;
    case '降水量':
      $index_rain = $i + 2;
    case '風向':
      $index_wind = $i + 2;
    case '風速':
      $index_wind_speed = $i + 2;
    case '湿度':
      $index_humi = $i + 2;
    case '気圧':
      $index_pres = $i + 2;
  }
}

$rc = preg_match_all('/<tr>.*?<td.*?>(.+?)<\/td>.*?' . str_repeat('<td.*?>(.+?)<\/td>', count($headers) - 1) . '.+?<\/tr>/s'
                     , $tmp[0], $matches, PREG_SET_ORDER);

array_shift($matches);

error_log(print_r($matches, TRUE));

$title = '';
for ($i = 0; $i < count($matches); $i++) {
  $hour = $matches[$i][1];
  $temp = $matches[$i][$index_temp];
  $rain = $matches[$i][$index_rain];
  $wind = $matches[$i][$index_wind] . $matches[$i][$index_wind_speed];
  $humi = $matches[$i][$index_humi];
  $pres = $matches[$i][$index_pres];
  if ($temp == '&nbsp;') {
    continue;
  }
  error_log("${pid} ${hour}時 ${temp}℃ ${humi}% ${rain}mm ${wind}m/s ${pres}hPa");
  $title = "${hour}時 ${temp}℃ ${humi}% ${rain}mm ${wind}m/s ${pres}hPa";
}

?>
