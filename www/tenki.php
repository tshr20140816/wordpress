<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi}");

const LIST_YOBI = array('日', '月', '火', '水', '木', '金', '土');

$mu = new MyUtils();

// Access Token
$access_token = $mu->get_access_token();

// Get Contexts
$list_context_id = $mu->get_contexts();

// holiday

$start_yyyy = date('Y');
$start_m = date('n');
$finish_yyyy = date('Y', strtotime('+1 month'));
$finish_m = date('n', strtotime('+1 month'));

$url = 'http://calendar-service.net/cal?start_year=' . $start_yyyy . '&start_mon=' . $start_m
  . '&end_year=' . $finish_yyyy . '&end_mon=' . $finish_m
  . '&year_style=normal&month_style=numeric&wday_style=ja_full&format=csv&holiday_only=1&zero_padding=1';

$res = $mu->get_contents($url);
$res = mb_convert_encoding($res, 'UTF-8', 'EUC-JP');

$tmp = explode("\n", $res);
array_shift($tmp);
array_pop($tmp);

$list_holiday = [];
for ($i = 0; $i < count($tmp); $i++) {
  $tmp1 = explode(',', $tmp[$i]);
  $timestamp = mktime(0, 0, 0, $tmp1[1], $tmp1[2], $tmp1[0]);
  $list_holiday[$timestamp] = $tmp1[7];
}
error_log($pid . ' $list_holiday : ' . print_r($list_holiday, TRUE));

// 24sekki

$list_24sekki = [];

$yyyy = (int)date('Y');
for ($j = 0; $j < 2; $j++) {
  $post_data = ['from_year' => $yyyy];

  $res = $mu->get_contents(
    'http://www.calc-site.com/calendars/solar_year',
    [CURLOPT_POST => TRUE,
     CURLOPT_POSTFIELDS => http_build_query($post_data),
    ]);
  
  $tmp = explode('<th>二十四節気</th>', $res);
  $tmp = explode('</table>', $tmp[1]);
  
  $tmp = explode('<tr>', $tmp[0]);
  array_shift($tmp);
  
  for ($i = 0; $i < count($tmp); $i++) {
    $rc = preg_match('/<td>(.+?)<.+?<.+?>(.+?)</', $tmp[$i], $matches);
    // error_log(print_r($matches, TRUE));
    $tmp1 = $matches[2];
    $tmp1 = str_replace('月', '-', $tmp1);
    $tmp1 = str_replace('日', '', $tmp1);
    $tmp1 = $yyyy . '-' . $tmp1;
    error_log($pid . ' ' . $tmp1 . ' ' . $matches[1]);
    $list_24sekki[strtotime($tmp1)] = '【' . $matches[1] . '】';
  }
  $yyyy++;
}
error_log($pid . ' $list_24sekki : ' . print_r($list_24sekki, TRUE));

// Sun rise set

$timestamp = time() + 9 * 60 * 60; // JST

$loop_count = date('m', $timestamp) === date('m', $timestamp + 10 * 24 * 60 * 60) ? 1 : 2;

$list_sunrise_sunset = [];
for ($j = 0; $j < $loop_count; $j++) {
  if ($j === 1) {
    $timestamp = time() + 9 * 60 * 60 + 10 * 24 * 60 * 60; // JST
  }
  $yyyy = date('Y', $timestamp);
  $mm = date('m', $timestamp);

  $res = $mu->get_contents('https://eco.mtk.nao.ac.jp/koyomi/dni/' . $yyyy . '/s' . getenv('AREA_ID') . $mm . '.html');

  $tmp = explode('<table ', $res);
  $tmp = explode('</table>', $tmp[1]);
  $tmp = explode('</tr>', $tmp[0]);
  array_shift($tmp);
  array_pop($tmp);

  $dt = date('Y-m-', $timestamp) . '01';

  for ($i = 0; $i < count($tmp); $i++) {
    $timestamp = strtotime("${dt} +${i} day"); // UTC
    $rc = preg_match('/.+?<\/td>.*?<td>(.+?)<\/td>.*?<td>.+?<\/td>.*?<td>.+?<\/td>.*?<td>.+?<\/td>.*?<td>(.+?)</', $tmp[$i], $matches);
    // error_log(trim($matches[1]));
    $list_sunrise_sunset[$timestamp] = '↗' . trim($matches[1]) . ' ↘' . trim($matches[2]);
  }
}
$subscript = '₀₁₂₃₄₅₆₇₈₉';
for ($i = 0; $i < 10; $i++) {
  $list_sunrise_sunset = str_replace($i, mb_substr($subscript, $i, 1), $list_sunrise_sunset);
}
error_log($pid . ' $list_sunrise_sunset : ' . print_r($list_sunrise_sunset, TRUE));

// Moon age

$timestamp = time() + 9 * 60 * 60; // JST

$loop_count = date('m', $timestamp) === date('m', $timestamp + 10 * 24 * 60 * 60) ? 1 : 2;

$list_moon_age = [];
for ($j = 0; $j < $loop_count; $j++) {
  if ($j === 1) {
    $timestamp = time() + 9 * 60 * 60 + 10 * 24 * 60 * 60; // JST
  }
  $yyyy = date('Y', $timestamp);
  $mm = date('m', $timestamp);

  $res = $mu->get_contents('https://eco.mtk.nao.ac.jp/koyomi/dni/' . $yyyy . '/m' . getenv('AREA_ID') . $mm . '.html');

  $tmp = explode('<table ', $res);
  $tmp = explode('</table>', $tmp[1]);
  $tmp = explode('</tr>', $tmp[0]);
  array_shift($tmp);
  array_pop($tmp);

  $dt = date('Y-m-', $timestamp) . '01';

  for ($i = 0; $i < count($tmp); $i++) {
    $timestamp = strtotime("${dt} +${i} day"); // UTC
    $rc = preg_match('/.+<td>(.+?)</', $tmp[$i], $matches);
    // error_log(trim($matches[1]));
    $list_moon_age[$timestamp] = '☽' . trim($matches[1]);
  }
}
$subscript = '₀₁₂₃₄₅₆₇₈₉';
for ($i = 0; $i < 10; $i++) {
  $list_moon_age = str_replace($i, mb_substr($subscript, $i, 1), $list_moon_age);
}
error_log($pid . ' $list_moon_age : ' . print_r($list_moon_age, TRUE));

// Weather Information

$res = $mu->get_contents('https://tenki.jp/week/' . getenv('LOCATION_NUMBER') . '/');

$rc = preg_match('/announce_datetime:(\d+-\d+-\d+) (\d+)/', $res, $matches);

error_log($pid . ' $matches[0] : ' . $matches[0]);
error_log($pid . ' $matches[1] : ' . $matches[1]);
error_log($pid . ' $matches[2] : ' . $matches[2]);

$dt = $matches[1]; // yyyy-mm-dd
$update_marker = ' _' . substr($matches[1], 8) . $matches[2] . '_'; // __DDHH__

// To Small Size
$subscript = '₀₁₂₃₄₅₆₇₈₉';
for ($i = 0; $i < 10; $i++) {
  $update_marker = str_replace($i, mb_substr($subscript, $i, 1), $update_marker);
}

$tmp = explode(getenv('POINT_NAME'), $res);
$tmp = explode('<td class="forecast-wrap">', $tmp[1]);
$list_weather = [];
for ($i = 0; $i < 10; $i++) {
  // ex) ##### 日曜日 01/13 ##### ☂/☀ 60% 25/18 _₁₀₁₀_
  $timestamp = strtotime("${dt} +${i} day");
  $list = explode("\n", str_replace(' ', '', trim(strip_tags($tmp[$i + 1]))));
  $tmp2 = $list[0];
  $tmp2 = str_replace('晴', '☀', $tmp2);
  $tmp2 = str_replace('曇', '☁', $tmp2);
  $tmp2 = str_replace('雨', '☂', $tmp2);
  $tmp2 = str_replace('のち', '/', $tmp2);
  $tmp2 = str_replace('時々', '|', $tmp2);
  $tmp2 = str_replace('一時', '|', $tmp2);
  $tmp3 = '##### '
    . LIST_YOBI[date('w', $timestamp)] . '曜日 '
    . date('m/d', $timestamp)
    . ' ##### '
    . $tmp2 . ' ' . $list[2] . ' ' . $list[1]
    . $update_marker;
  
  if (array_key_exists($timestamp, $list_holiday)) {
    $tmp3 = str_replace(' #####', ' ★' . $list_holiday[$timestamp] . '★ #####', $tmp3);
  }
  if (array_key_exists($timestamp, $list_24sekki)) {
    $tmp3 .= $list_24sekki[$timestamp];
  }
  if (array_key_exists($timestamp, $list_sunrise_sunset)) {
    $tmp3 .= ' ' . $list_sunrise_sunset[$timestamp];
  }
  if (array_key_exists($timestamp, $list_moon_age)) {
    $tmp3 .= ' ' . $list_moon_age[$timestamp];
  }
  
  error_log("${pid} ${tmp3}");

  $list_weather[] = '{"title":"' . $tmp3
    . '","duedate":"' . $timestamp
    . '","context":"' . $list_context_id[date('w', $timestamp)]
    . '","tag":"WEATHER","folder":"__FOLDER_ID__"}';
}

if (count($list_weather) == 0) {
  error_log("${pid} WEATHER DATA NONE");
  exit();
}

// Get Tasks

$url = 'https://api.toodledo.com/3/tasks/get.php?access_token=' . $access_token . '&comp=0&fields=tag'
  . '&after=' . strtotime('-2 day');
$res = $mu->get_contents($url);
// error_log($res);

$tasks = json_decode($res, TRUE);
// error_log(print_r($tasks, TRUE));
$list_delete_task = [];
for ($i = 0; $i < count($tasks); $i++) {
  if (array_key_exists('id', $tasks[$i]) && array_key_exists('tag', $tasks[$i])) {
    if ($tasks[$i]['tag'] == 'WEATHER') {
      $list_delete_task[] = $tasks[$i]['id'];
      // error_log("${pid} DELETE TARGET TASK ID : " . $tasks[$i]['id']);
      if (count($list_delete_task) == 50) {
        break;
      }
    }
  }
}
error_log($pid . ' $list_delete_task : ' . print_r($list_delete_task, TRUE));

// Get Folders
$label_folder_id = $mu->get_folder_id('LABEL');

// Add Tasks

$tmp = implode(',', $list_weather);
$tmp = str_replace('__FOLDER_ID__', $label_folder_id, $tmp);
$post_data = ['access_token' => $access_token, 'tasks' => '[' . $tmp . ']'];

// error_log(http_build_query($post_data));

$res = $mu->get_contents(
  'https://api.toodledo.com/3/tasks/add.php',
  [CURLOPT_POST => TRUE,
   CURLOPT_POSTFIELDS => http_build_query($post_data),
  ]);

error_log("${pid} add.php RESPONSE : ${res}");

// Delete Tasks

error_log("${pid} DELETE TARGET TASK COUNT : " . count($list_delete_task));

if (count($list_delete_task) > 0) {
  $post_data = ['access_token' => $access_token, 'tasks' => '[' . implode(',', $list_delete_task) . ']'];  
  $res = $mu->get_contents(
    'https://api.toodledo.com/3/tasks/delete.php',
    [CURLOPT_POST => TRUE,
     CURLOPT_POSTFIELDS => http_build_query($post_data),
    ]);
  error_log("${pid} delete.php RESPONSE : ${res}");
}

error_log("${pid} FINISH");

$res = $mu->get_contents(
  'https://' . getenv('HEROKU_APP_NAME') . '.herokuapp.com/add_label.php',
  [CURLOPT_USERPWD => getenv('BASIC_USER') . ':' . getenv('BASIC_PASSWORD'),
  ]);

exit();
?>
