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
$list_sunrise_sunset = $mu->to_small_size($list_sunrise_sunset);
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
$list_moon_age = $mu->to_small_size($list_moon_age);
error_log($pid . ' $list_moon_age : ' . print_r($list_moon_age, TRUE));

// quota

$api_key = getenv('API_KEY');
$url = 'https://api.heroku.com/account';

$res = $mu->get_contents(
  $url,
  [CURLOPT_HTTPHEADER => ['Accept: application/vnd.heroku+json; version=3',
                          "Authorization: Bearer ${api_key}",
                         ]]);

$data = json_decode($res, TRUE);

$url = "https://api.heroku.com/accounts/${data['id']}/actions/get-quota";

$res = $mu->get_contents(
  $url,
  [CURLOPT_HTTPHEADER => ['Accept: application/vnd.heroku+json; version=3.account-quotas',
                          "Authorization: Bearer ${api_key}",
                         ]]);

$data = json_decode($res, TRUE);

$dyno_used = (int)$data['quota_used'];
$dyno_quota = (int)$data['account_quota'];

error_log($pid . ' $dyno_used : ' . $dyno_used);
error_log($pid . ' $dyno_quota : ' . $dyno_quota);

$tmp = $dyno_quota - $dyno_used;
$tmp = floor($tmp / 86400) . 'd ' . ($tmp / 3600 % 24) . 'h ' . ($tmp / 60 % 60) . 'm';

$update_marker = $mu->to_small_size(' _' . date('Ymd His', strtotime('+ 9 hours')) . '_');

$quota_task = '{"title":"quota : ' . $tmp . $update_marker
  . '","duedate":"' . mktime(0, 0, 0, 1, 1, 2018)
  . '","context":"' . $list_context_id[date('w', mktime(0, 0, 0, 1, 1, 2018))]
  . '","tag":"WEATHER","folder":"__FOLDER_ID__"}';

// Weather Information

$res = $mu->get_contents('https://tenki.jp/week/' . getenv('LOCATION_NUMBER') . '/');

$rc = preg_match('/announce_datetime:(\d+-\d+-\d+) (\d+)/', $res, $matches);

error_log($pid . ' $matches[0] : ' . $matches[0]);
error_log($pid . ' $matches[1] : ' . $matches[1]);
error_log($pid . ' $matches[2] : ' . $matches[2]);

$dt = $matches[1]; // yyyy-mm-dd

$update_marker = $mu->to_small_size(' _' . substr($matches[1], 8) . $matches[2] . '_'); // __DDHH__

$tmp = explode(getenv('POINT_NAME'), $res);
$tmp = explode('<td class="forecast-wrap">', $tmp[1]);
$list_add_task = [];
for ($i = 0; $i < 10; $i++) {
  $timestamp = strtotime("${dt} +${i} day");
  $list = explode("\n", str_replace(' ', '', trim(strip_tags($tmp[$i + 1]))));
  $tmp2 = $list[0];
  $tmp2 = str_replace('晴', '☀', $tmp2);
  $tmp2 = str_replace('曇', '☁', $tmp2);
  $tmp2 = str_replace('雨', '☂', $tmp2);
  $tmp2 = str_replace('のち', '/', $tmp2);
  $tmp2 = str_replace('時々', '|', $tmp2);
  $tmp2 = str_replace('一時', '|', $tmp2);
  $tmp3 = '### '
    . LIST_YOBI[date('w', $timestamp)] . '曜日 '
    . date('m/d', $timestamp)
    . ' ### '
    . $tmp2 . ' ' . $list[2] . ' ' . $list[1]
    . $update_marker;

  if (array_key_exists($timestamp, $list_holiday)) {
    $tmp3 = str_replace(' ###', ' ★' . $list_holiday[$timestamp] . '★ ###', $tmp3);
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

  $list_add_task[] = '{"title":"' . $tmp3
    . '","duedate":"' . $timestamp
    . '","context":"' . $list_context_id[date('w', $timestamp)]
    . '","tag":"WEATHER","folder":"__FOLDER_ID__"}';
}

if (count($list_add_task) == 0) {
  error_log("${pid} WEATHER DATA NONE");
  exit();
}

$list_add_task[] = $quota_task;

// Weather Information (Guest)

$list_weather_guest_area = $mu->get_weather_guest_area();

for ($i = 0; $i < count($list_weather_guest_area); $i++) {
  $tmp = explode(',', $list_weather_guest_area[$i]);
  $location_number = $tmp[0];
  $point_name = $tmp[1];
  $yyyymmdd = $tmp[2];
  if ((int)$yyyymmdd < (int)date('Ymd', strtotime('+11 days'))) {
    $res = $mu->get_contents('https://tenki.jp/week/' . $location_number . '/');
    $rc = preg_match('/announce_datetime:(\d+-\d+-\d+) (\d+)/', $res, $matches);
    $dt = $matches[1]; // yyyy-mm-dd
    
    $tmp = explode($point_name, $res);
    $tmp = explode('<td class="forecast-wrap">', $tmp[1]);
    for ($j = 0; $j < 10; $j++) {
      $timestamp = strtotime("${dt} +${j} day");
      if (date('Ymd', $timestamp) == $yyyymmdd) {
        $list = explode("\n", str_replace(' ', '', trim(strip_tags($tmp[$j + 1]))));
        $tmp2 = date('m/d', $timestamp) . ' ' . $point_name . ' ' . $list[0] . ' ' . $list[2] . ' ' . $list[1];
        error_log($tmp2);
      }
    }
  }
}

// Get Tasks

$url = 'https://api.toodledo.com/3/tasks/get.php?comp=0&fields=tag,duedate,context&access_token=' . $access_token
  . '&after=' . strtotime('-2 day');
$res = $mu->get_contents($url);

$tasks = json_decode($res, TRUE);

// for cache
file_put_contents('/tmp/tasks_tenki', serialize($tasks));

$list_delete_task = [];
for ($i = 0; $i < count($tasks); $i++) {
  if (array_key_exists('id', $tasks[$i]) && array_key_exists('tag', $tasks[$i])) {
    if ($tasks[$i]['tag'] == 'WEATHER') {
      $list_delete_task[] = $tasks[$i]['id'];
    }
  }
}
error_log($pid . ' $list_delete_task : ' . print_r($list_delete_task, TRUE));

// Get Folders
$label_folder_id = $mu->get_folder_id('LABEL');

// Add Tasks
$list_add_task = str_replace('__FOLDER_ID__', $label_folder_id, $list_add_task);
$rc = $mu->add_tasks($list_add_task);

// Delete Tasks
$mu->delete_tasks($list_delete_task);

error_log("${pid} FINISH");

$res = $mu->get_contents(
  'https://' . getenv('HEROKU_APP_NAME') . '.herokuapp.com/add_label.php',
  [CURLOPT_USERPWD => getenv('BASIC_USER') . ':' . getenv('BASIC_PASSWORD'),
  ]);

exit();
?>
