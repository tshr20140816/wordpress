<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$list_weather_guest_area = $mu->get_weather_guest_area();

$update_marker = $mu->to_small_size(' _' . date('Ymd', strtotime('+9 hours')) . '_');
for ($i = 0; $i < count($list_weather_guest_area); $i++) {
  $is_add_flag = FALSE;
  $tmp = explode(',', $list_weather_guest_area[$i]);
  $location_number = $tmp[0];
  $point_name = $tmp[1];
  $yyyymmdd = $tmp[2];
  $timestamp = strtotime($yyyymmdd);
  if ((int)$yyyymmdd < (int)date('Ymd', strtotime('+11 days') + 9 * 60 * 60)) {
    $res = $mu->get_contents('https://tenki.jp/week/' . $location_number . '/');
    $rc = preg_match('/announce_datetime:(\d+-\d+-\d+) (\d+)/', $res, $matches);
    $dt = $matches[1]; // yyyy-mm-dd
    $tmp = explode($point_name, $res);
    $tmp = explode('<td class="forecast-wrap">', $tmp[1]);
    for ($j = 0; $j < 10; $j++) {
      $timestamp = strtotime("${dt} +${j} day") + 9 * 60 * 90;
      if (date('Ymd', $timestamp) == $yyyymmdd) {
        $list = explode("\n", str_replace(' ', '', trim(strip_tags($tmp[$j + 1]))));
        $title = date('m/d', $timestamp) . " 【${point_name} ${list[0]} ${list[2]} ${list[1]}】${update_marker}";
        $is_add_flag = TRUE;
        break;
      }
    }
  }
  if ($is_add_flag === FALSE) {
    $title = date('m/d', $timestamp) . " 【${point_name} 天気予報未取得】${update_marker}";
  }
  $tmp = str_replace('__TITLE__', $title, $template_add_task);
  $tmp = str_replace('__DUEDATE__', $timestamp, $tmp);
  $tmp = str_replace('__CONTEXT__', $list_context_id[date('w', $timestamp)], $tmp);
  $list_add_task[] = $tmp;
}

error_log(print_r($list_add_task, TRUE));

exit();

$res = $mu->get_contents(getenv('URL_TAIKAN_SHISU'));

// error_log($res);

$rc = preg_match('/<!-- today index -->.+?<span class="indexes-telop-0">(.+?)<\/span>/s', $res, $matches);

error_log(print_r($matches, TRUE));

$res = $mu->get_contents(getenv('URL_KASA_SHISU2'));

$rc = preg_match('/<!-- today index -->.+?<span class="indexes-telop-0">(.+?)<\/span>/s', $res, $matches);

error_log(print_r($matches, TRUE));

$rc = preg_match('/<!-- tomorrow index -->.+?<span class="indexes-telop-0">(.+?)<\/span>/s', $res, $matches);

error_log(print_r($matches, TRUE));

$rc = preg_match('/<!-- week -->(.+?)<!-- \/week -->/s', $res, $matches);
$rc = preg_match_all('/<p class="indexes-telop-0">(.+?)<\/p>/s', $matches[1], $matches2, PREG_SET_ORDER);

error_log(print_r($matches2, TRUE));

?>
