<?php

error_log('START');

$list_base = [];
for ($i = 0; $i < 3; $i++) {
  $url = 'https://feed43.com/' . getenv('SUB_ADDRESS') . ($i * 5 + 11) . '-' . ($i * 5 + 15) . '.xml';
  error_log($url);
  $res = get_contents($url, NULL);
  error_log($res);
  foreach (explode("\n", $res) as $one_line) {
    if (strpos($one_line, '<title>_') !== FALSE) {
      // error_log($one_line);
      $tmp = explode('_', $one_line);
      $list_base[$tmp[1]] = $tmp[2];
    }
  }
}
error_log(print_r($list_base, TRUE));

$list_weather = [];
$list_yobi = array('日', '月', '火', '水', '木', '金', '土');
$update_marker = ' __' . date('ymd') . '__';
// To Small Size
$subscript = '₀₁₂₃₄₅₆₇₈₉';
for ($i = 0; $i < 10; $i++) {
  $update_marker = str_replace($i, mb_substr($subscript, $i, 1), $update_marker);
}
for ($i = 0; $i < 15; $i++) {
  $timestamp = strtotime('+' . ($i + 10) . ' days');
  $dt = date('n/j', $timestamp);
  error_log($dt);
  if (array_key_exists($dt, $list_base)) {
    $tmp = $list_base[$dt];
  } else {
    $tmp = '----';
  }
  $tmp = '##### ' . $list_yobi[date('w', $timestamp)] . '曜日 ' . date('m/d', $timestamp) . ' ##### ' . $tmp . $update_marker;
  $list_weather[] = '{"title":"' . $tmp . '","duedate":"' . $timestamp . '","tag":"WEATHER2","folder":"__FOLDER_ID__"}';
}
error_log(print_r($list_weather, TRUE));

function get_contents($url_, $options_) {
  $ch = curl_init();
  curl_setopt_array($ch, [
    CURLOPT_URL => $url_,
    CURLOPT_RETURNTRANSFER => TRUE,
    CURLOPT_ENCODING => '',
    CURLOPT_FOLLOWLOCATION => 1,
    CURLOPT_MAXREDIRS => 3,
    CURLOPT_SSL_FALSESTART => TRUE,
    ]);
  if (is_null($options_) == FALSE) {
    curl_setopt_array($ch, $options_);
  }
  $res = curl_exec($ch);
  curl_close($ch);
  
  return $res;
}
?>
