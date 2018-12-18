<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s'));

const LIST_YOBI = array('日', '月', '火', '水', '木', '金', '土');

$mu = new MyUtils();

$hour_now = ((int)date('G') + 9) % 24; // JST

// outlet parking information ここでは呼び捨て 後で回収

$file_outlet_parking_information = '/tmp/outlet_parking_information.txt';
@unlink($file_outlet_parking_information);

$url = 'https://' . getenv('HEROKU_APP_NAME') . '.herokuapp.com/outlet_parking_information.php';
$options = [
  CURLOPT_TIMEOUT => 3,
  CURLOPT_USERPWD => getenv('BASIC_USER') . ':' . getenv('BASIC_PASSWORD'),
  ];
$res = $mu->get_contents($url, $options);

// Access Token
$access_token = $mu->get_access_token();

// Get Folders
$folder_id_work = $mu->get_folder_id('WORK');
$folder_id_label = $mu->get_folder_id('LABEL');

// Get Contexts
$list_context_id = $mu->get_contexts();

$list_add_task = [];

if ($hour_now % 2 === 1) {

  // holiday
  $list_holiday = get_holiday($mu);

  // 24sekki
  $list_24sekki = get_24sekki($mu);

  // Sun rise set
  $list_sunrise_sunset = get_sun_rise_set($mu);

  // Moon age
  $list_moon_age = get_moon_age($mu);

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

  $template_add_task = '{"title":"__TITLE__","duedate":"__DUEDATE__","context":"__CONTEXT__","tag":"WEATHER","folder":"__FOLDER_ID__"}';
  $template_add_task = str_replace('__FOLDER_ID__', $folder_id_label, $template_add_task);
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

    $tmp4 = str_replace('__TITLE__', $tmp3, $template_add_task);
    $tmp4 = str_replace('__DUEDATE__', $timestamp, $tmp4);
    $tmp4 = str_replace('__CONTEXT__', $list_context_id[date('w', $timestamp)], $tmp4);

    $list_add_task[] = $tmp4;
  }

  // Weather Information (Guest)

  $list_weather_guest_area = $mu->get_weather_guest_area();

  $update_marker = $mu->to_small_size(' _' . date('Ymd') . '_');
  for ($i = 0; $i < count($list_weather_guest_area); $i++) {
    $is_add_flag = FALSE;
    $tmp = explode(',', $list_weather_guest_area[$i]);
    $location_number = $tmp[0];
    $point_name = $tmp[1];
    $yyyymmdd = $tmp[2];
    $timestamp = strtotime($yyyymmdd);
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
}

// amedas
$list_add_task = array_merge($list_add_task, get_task_amedas($mu));

// Rainfall
$list_add_task = array_merge($list_add_task, get_task_rainfall($mu));

// Quota
$list_add_task = array_merge($list_add_task, get_task_quota($mu));

// parking information
$list_add_task = array_merge($list_add_task, get_task_parking_information($mu, $file_outlet_parking_information));

// Get Tasks
$url = 'https://api.toodledo.com/3/tasks/get.php?comp=0&fields=tag,duedate,context,star,folder&access_token=' . $access_token;
$res = $mu->get_contents($url);
$tasks = json_decode($res, TRUE);

error_log($pid . ' TASKS COUNT : ' . count($tasks));

// 予定有りでラベル無しの日のラベル追加

$list_label_task = [];
$list_schedule_task = [];
for ($i = 0; $i < count($tasks); $i++) {
  if (array_key_exists('duedate', $tasks[$i]) && array_key_exists('folder', $tasks[$i])) {
    if ($tasks[$i]['folder'] == $folder_id_label) {
      $list_label_task[] = $tasks[$i]['duedate'];
    } else {
      $list_schedule_task[] = $tasks[$i]['duedate'];
    }
  }
}

$list_non_label = array_unique(array_diff($list_schedule_task, $list_label_task));
sort($list_non_label);
error_log($pid . ' $list_non_label : ' . print_r($list_non_label, TRUE));

$timestamp = strtotime('+20 day');
for ($i = 0; $i < count($list_non_label); $i++) {
  if ($list_non_label[$i] > $timestamp) {

    $yyyy = $mu->to_small_size(date('Y', $list_non_label[$i]));

    $tmp = '### ' . LIST_YOBI[date('w', $list_non_label[$i])] . '曜日 ' . date('m/d', $list_non_label[$i]) . ' ### ' . $yyyy;
    $list_add_task[] = '{"title":"' . $tmp
      . '","duedate":"' . $list_non_label[$i]
      . '","context":"' . $list_context_id[date('w', $list_non_label[$i])]
      . '","tag":"ADDITIONAL","folder":"' . $folder_id_label . '"}';
  }
}

// 削除タスク抽出

$is_exists_no_duedate_task = FALSE;
$list_delete_task = [];
for ($i = 0; $i < count($tasks); $i++) {
  if (array_key_exists('id', $tasks[$i]) && array_key_exists('tag', $tasks[$i])) {
    if ($tasks[$i]['tag'] == 'HOURLY' || ($hour_now % 2 === 1 && $tasks[$i]['tag'] == 'WEATHER')) {
      $list_delete_task[] = $tasks[$i]['id'];
    } else if ($tasks[$i]['duedate'] == 0) {
      $is_exists_no_duedate_task = TRUE;
    }
  }
}
error_log($pid . ' $list_delete_task : ' . print_r($list_delete_task, TRUE));

// 日付(duedate)設定漏れ警告

if ($is_exists_no_duedate_task === TRUE) {
  $list_add_task[] = '{"title":"NO DUEDATE TASK EXISTS","duedate":"' . mktime(0, 0, 0, 1, 1, 2018)
      . '","context":"' . $list_context_id[date('w', mktime(0, 0, 0, 1, 1, 2018))]
      . '","tag":"HOURLY","folder":"' . $folder_id_label . '"}';
}

error_log($pid . ' $list_add_task : ' . print_r($list_add_task, TRUE));

// WORK & Star の日付更新

$list_edit_task = [];
$template_edit_task = '{"id":"__ID__","title":"__TITLE__","context":"__CONTEXT__"}';
for ($i = 0; $i < count($tasks); $i++) {
  if (array_key_exists('id', $tasks[$i]) && array_key_exists('folder', $tasks[$i])) {
    if ($tasks[$i]['folder'] == $folder_id_work && $tasks[$i]['star'] == '1') {
      $duedate = $tasks[$i]['duedate'];
      $title = $tasks[$i]['title'];
      if (substr($title, 0, 5) == date('m/d', $duedate)) {
        continue;
      }
      $tmp = str_replace('__ID__', $tasks[$i]['id'], $template_edit_task);
      $tmp = str_replace('__TITLE__', date('m/d', $duedate) . substr($title, 5), $tmp);
      $tmp = str_replace('__CONTEXT__', $list_context_id[date('w', $duedate)], $tmp);
      $list_edit_task[] = $tmp;
    }
  }
}

// duedate と context の不一致更新

$template_edit_task = '{"id":"__ID__","context":"__CONTEXT__"}';
for ($i = 0; $i < count($tasks); $i++) {
  if (array_key_exists('id', $tasks[$i])) {
    $real_context_id = $list_context_id[date('w', $tasks[$i]['duedate'])];
    $task_context_id = $tasks[$i]['context'];
    if ($task_context_id == '0' || $task_context_id != $real_context_id) {
      error_log($pid . ' $tasks[$i] : ' . print_r($tasks[$i], TRUE));
      $tmp = str_replace('__ID__', $tasks[$i]['id'], $template_edit_task);
      $tmp = str_replace('__CONTEXT__', $real_context_id, $tmp);
      $list_edit_task[] = $tmp;
    }
  }
}

error_log($pid . ' $list_edit_task : ' . print_r($list_edit_task, TRUE));

// Add Tasks
$rc = $mu->add_tasks($list_add_task);

// Edit Tasks
$rc = $mu->edit_tasks($list_edit_task);

// Delete Tasks
$mu->delete_tasks($list_delete_task);

error_log("${pid} FINISH");

exit();

function get_task_parking_information($mu_, $file_outlet_parking_information_) {

  // Get Folders
  $folder_id_label = $mu_->get_folder_id('LABEL');

  // Get Contexts
  $list_context_id = $mu_->get_contexts();

  const LIST_PARKING = array(' ', '体', 'ク', 'セ', 'シ');
  
  $list_add_task = [];
  
  $update_marker = $mu_->to_small_size(' _' . date('Ymd Hi', strtotime('+ 9 hours')) . '_');
  
  $parking_information_all = '';
  for ($i = 1; $i < 5; $i++) {
    $url = 'http://www.motomachi-pa.jp/cgi/manku.pl?park_id=' . $i . '&mode=pc';
    $res = $mu_->get_contents($url);
    
    $hash_text = hash('sha512', $res);
    
    $pdo = $mu_->get_pdo();
    
    $sql = <<< __HEREDOC__
SELECT T1.parse_text
  FROM t_imageparsehash T1
 WHERE T1.group_id = 2
   AND T1.hash_text = :b_hash_text;
__HEREDOC__;
    
    $statement = $pdo->prepare($sql);
    $rc = $statement->execute([':b_hash_text' => $hash_text]);
    error_log(getmypid() . ' [' . __METHOD__ . '] SELECT RESULT : ' . $rc);
    $results = $statement->fetchAll();
    // error_log(getmypid() . ' [' . __METHOD__ . '] $results : ' . print_r($results, TRUE));
    
    $parse_text = '';
    foreach ($results as $row) {
      $parse_text = $row['parse_text'];
    }
    
    $pdo = NULL;
    
    if (strlen($parse_text) == 0) {
      $parse_text = '不明';
      error_log(getmypid() . ' [' . __METHOD__ . '] $hash_text : ' . $hash_text);
    }
    $parking_information_all .= ' [' . LIST_PARKING[$i] . "]${parse_text}";
  }
  
  for ($i = 0; $i < 20; $i++) {
    if (file_exists($file_outlet_parking_information_) === TRUE) {
      break;
    }
    error_log(getmypid() . ' [' . __METHOD__ . '] waiting ' . $i);
    sleep(1);
  }

  if (file_exists($file_outlet_parking_information_) === TRUE) {
    $list_add_task[] = '{"title":"P ' . file_get_contents($file_outlet_parking_information_) . $parking_information_all . $update_marker
      . '","duedate":"' . mktime(0, 0, 0, 1, 5, 2018)
      . '","context":"' . $list_context_id[date('w', mktime(0, 0, 0, 1, 5, 2018))]
      . '","tag":"HOURLY","folder":"' . $folder_id_label . '"}';
  }

  error_log(getmypid() . ' [' . __METHOD__ . '] TASKS PARKING INFORMATION : ' . print_r($list_add_task, TRUE));
  return $list_add_task;
}

function get_task_amedas($mu_) {

  // Get Folders
  $folder_id_label = $mu_->get_folder_id('LABEL');

  // Get Contexts
  $list_context_id = $mu_->get_contexts();

  $list_add_task = [];

  $res = $mu_->get_contents(getenv('URL_AMEDAS'));

  $tmp = explode('">時刻</td>', $res);
  $tmp = explode('</table>', $tmp[1]);

  $tmp1 = explode('</tr>', $tmp[0]);
  $headers = explode('</td>', $tmp1[0]);
  error_log($pid . ' [' . __METHOD__ . '] $headers : ' . print_r($headers, TRUE));

  for ($i = 0; $i < count($headers); $i++) {
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
    // error_log(getmypid() . " ${hour}時 ${temp}℃ ${humi}% ${rain}mm ${wind}m/s ${pres}hPa");
    $title = "${hour}時 ${temp}℃ ${humi}% ${rain}mm ${wind}m/s ${pres}hPa";
  }

  // 警報 注意報
  
  $url = getenv('URL_WEATHER_WARN');  
  $res = $mu_->get_contents($url);
  
  $rc = preg_match_all('/<ul class="warnDetail_head_labels">(.+?)<\/ul>/s', $res, $matches, PREG_SET_ORDER);
  $tmp = preg_replace('/<.+?>/s', ' ', $matches[0][1]);
  $warn = trim(preg_replace('/\s+/s', ' ', $tmp));
  
  if ($title != '') {
    $list_add_task[] = '{"title":"' . $title . ' ' . $warn
      . '","duedate":"' . mktime(0, 0, 0, 1, 2, 2018)
      . '","context":"' . $list_context_id[date('w', mktime(0, 0, 0, 1, 2, 2018))]
      . '","tag":"HOURLY","folder":"' . $folder_id_label . '"}';
  }

  error_log(getmypid() . ' [' . __METHOD__ . '] TASKS AMEDAS : ' . print_r($list_add_task, TRUE));
  return $list_add_task;
}

function get_task_rainfall($mu_) {

  // Get Folders
  $folder_id_label = $mu_->get_folder_id('LABEL');

  // Get Contexts
  $list_context_id = $mu_->get_contexts();

  $list_add_task = [];

  $res = $mu_->get_contents(getenv('URL_KASA_SHISU'));
  
  $rc = preg_match('/<!--指数情報-->.+?<span>傘指数(.+?)<.+?<p class="index_text">(.+?)</s', $res, $matches);
  $suffix = ' 傘指数' . $matches[1] . ' ' . $matches[2];  
  
  $url = 'https://map.yahooapis.jp/geoapi/V1/reverseGeoCoder?output=json&appid=' . getenv('YAHOO_API_KEY')
    . '&lon=' . getenv('LONGITUDE') . '&lat=' . getenv('LATITUDE');
  $res = $mu_->get_contents($url, NULL, TRUE);
  $data = json_decode($res, TRUE);
  error_log(getmypid() . ' [' . __METHOD__ . '] $data : ' . print_r($data, TRUE));

  $url = 'https://map.yahooapis.jp/weather/V1/place?interval=5&output=json&appid=' . getenv('YAHOO_API_KEY')
    . '&coordinates=' . getenv('LONGITUDE') . ',' . getenv('LATITUDE');
  $res = $mu_->get_contents($url);

  $data = json_decode($res, TRUE);
  error_log(getmypid() . ' [' . __METHOD__ . '] $data : ' . print_r($data, TRUE));
  $data = $data['Feature'][0]['Property']['WeatherList']['Weather'];

  $list = [];
  for ($i = 0; $i < count($data); $i++) {
    if ($data[$i]['Rainfall'] != '0') {
      $list[] = $mu_->to_small_size(substr($data[$i]['Date'], 8)) . ' ' . $data[$i]['Rainfall'];
    }
  }
  if (count($list) > 0) {
    $tmp = date('H:i', strtotime('+9 hours')) . ' ☂ ' . implode(' ', $list);
  } else {
    $tmp = date('H:i', strtotime('+9 hours')) . ' ☀';
  }
  $list_add_task[] = '{"title":"' . $tmp . $suffix
      . '","duedate":"' . mktime(0, 0, 0, 1, 1, 2018)
      . '","context":"' . $list_context_id[date('w', mktime(0, 0, 0, 1, 1, 2018))]
      . '","tag":"HOURLY","folder":"' . $folder_id_label . '"}';

  error_log(getmypid() . ' [' . __METHOD__ . '] TASKS RAINFALL : ' . print_r($list_add_task, TRUE));
  return $list_add_task;
}

function get_task_quota($mu_) {

  // Get Folders
  $folder_id_label = $mu_->get_folder_id('LABEL');
  // Get Contexts
  $list_context_id = $mu_->get_contexts();

  $api_key = getenv('HEROKU_API_KEY');
  $url = 'https://api.heroku.com/account';

  $res = $mu_->get_contents(
    $url,
    [CURLOPT_HTTPHEADER => ['Accept: application/vnd.heroku+json; version=3',
                            "Authorization: Bearer ${api_key}",
                           ]],
    TRUE);

  $data = json_decode($res, TRUE);
  error_log(getmypid() . ' [' . __METHOD__ . '] $data : ' . print_r($data, TRUE));
  $account = explode('@', $data['email'])[0];
  $url = "https://api.heroku.com/accounts/${data['id']}/actions/get-quota";

  $res = $mu_->get_contents(
    $url,
    [CURLOPT_HTTPHEADER => ['Accept: application/vnd.heroku+json; version=3.account-quotas',
                            "Authorization: Bearer ${api_key}",
                           ]]);

  $data = json_decode($res, TRUE);
  error_log(getmypid() . ' [' . __METHOD__ . '] $data : ' . print_r($data, TRUE));

  $dyno_used = (int)$data['quota_used'];
  $dyno_quota = (int)$data['account_quota'];

  error_log(getmypid() . ' [' . __METHOD__ . '] $dyno_used : ' . $dyno_used);
  error_log(getmypid() . ' [' . __METHOD__ . '] $dyno_quota : ' . $dyno_quota);

  $tmp = $dyno_quota - $dyno_used;
  $tmp = floor($tmp / 86400) . 'd ' . ($tmp / 3600 % 24) . 'h ' . ($tmp / 60 % 60) . 'm';

  $update_marker = $mu_->to_small_size(' _' . date('Ymd Hi', strtotime('+ 9 hours')) . '_');

  $list_add_task[] = '{"title":"' . $account . ' : ' . $tmp . $update_marker
    . '","duedate":"' . mktime(0, 0, 0, 1, 3, 2018)
    . '","context":"' . $list_context_id[date('w', mktime(0, 0, 0, 1, 3, 2018))]
    . '","tag":"HOURLY","folder":"' . $folder_id_label . '"}';

  error_log(getmypid() . ' [' . __METHOD__ . '] TASKS QUOTA : ' . print_r($list_add_task, TRUE));
  return $list_add_task;
}

function get_holiday($mu_) {

  $start_yyyy = date('Y');
  $start_m = date('n');
  $finish_yyyy = date('Y', strtotime('+1 month'));
  $finish_m = date('n', strtotime('+1 month'));

  $url = 'http://calendar-service.net/cal?start_year=' . $start_yyyy . '&start_mon=' . $start_m
    . '&end_year=' . $finish_yyyy . '&end_mon=' . $finish_m
    . '&year_style=normal&month_style=numeric&wday_style=ja_full&format=csv&holiday_only=1&zero_padding=1';

  $res = $mu_->get_contents($url, NULL, TRUE);
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
  error_log(getmypid() . ' [' . __METHOD__ . '] $list_holiday : ' . print_r($list_holiday, TRUE));

  return $list_holiday;
}

function get_24sekki($mu_) {

  $list_24sekki = [];

  $yyyy = (int)date('Y');
  for ($j = 0; $j < 2; $j++) {
    $post_data = ['from_year' => $yyyy];

    $res = $mu_->get_contents(
      'http://www.calc-site.com/calendars/solar_year',
      [CURLOPT_POST => TRUE,
       CURLOPT_POSTFIELDS => http_build_query($post_data),
      ],
      TRUE);

    $tmp = explode('<th>二十四節気</th>', $res);
    $tmp = explode('</table>', $tmp[1]);

    $tmp = explode('<tr>', $tmp[0]);
    array_shift($tmp);

    for ($i = 0; $i < count($tmp); $i++) {
      $rc = preg_match('/<td>(.+?)<.+?<.+?>(.+?)</', $tmp[$i], $matches);
      $tmp1 = $matches[2];
      $tmp1 = str_replace('月', '-', $tmp1);
      $tmp1 = str_replace('日', '', $tmp1);
      $tmp1 = $yyyy . '-' . $tmp1;
      error_log(getmypid() . ' [' . __METHOD__ . "] ${tmp1} " . $matches[1]);
      $list_24sekki[strtotime($tmp1)] = '【' . $matches[1] . '】';
    }
    $yyyy++;
  }
  error_log(getmypid() . ' [' . __METHOD__ . '] $list_holiday : ' . print_r($list_24sekki, TRUE));

  return $list_24sekki;
}

function get_sun_rise_set($mu_) {

  $timestamp = time() + 9 * 60 * 60; // JST
  // 10日後が翌月になるときは2か月分取得
  $loop_count = date('m', $timestamp) === date('m', $timestamp + 10 * 24 * 60 * 60) ? 1 : 2;

  $list_sunrise_sunset = [];
  for ($j = 0; $j < $loop_count; $j++) {
    if ($j === 1) {
      $timestamp = time() + 9 * 60 * 60 + 10 * 24 * 60 * 60; // JST
    }
    $yyyy = date('Y', $timestamp);
    $mm = date('m', $timestamp);

    $res = $mu_->get_contents('https://eco.mtk.nao.ac.jp/koyomi/dni/' . $yyyy . '/s' . getenv('AREA_ID') . $mm . '.html', NULL, TRUE);

    $tmp = explode('<table ', $res);
    $tmp = explode('</table>', $tmp[1]);
    $tmp = explode('</tr>', $tmp[0]);
    array_shift($tmp);
    array_pop($tmp);

    $dt = date('Y-m-', $timestamp) . '01';

    for ($i = 0; $i < count($tmp); $i++) {
      $timestamp = strtotime("${dt} +${i} day"); // UTC
      $rc = preg_match('/.+?<\/td>.*?<td>(.+?)<\/td>.*?<td>.+?<\/td>.*?<td>.+?<\/td>.*?<td>.+?<\/td>.*?<td>(.+?)</', $tmp[$i], $matches);
      $list_sunrise_sunset[$timestamp] = '↗' . trim($matches[1]) . ' ↘' . trim($matches[2]);
    }
  }
  $list_sunrise_sunset = $mu_->to_small_size($list_sunrise_sunset);
  error_log(getmypid() . ' [' . __METHOD__ . '] $list_sunrise_sunset : ' . print_r($list_sunrise_sunset, TRUE));

  return $list_sunrise_sunset;
}

function get_moon_age($mu_) {

  $timestamp = time() + 9 * 60 * 60; // JST
  // 10日後が翌月になるときは2か月分取得
  $loop_count = date('m', $timestamp) === date('m', $timestamp + 10 * 24 * 60 * 60) ? 1 : 2;

  $list_moon_age = [];
  for ($j = 0; $j < $loop_count; $j++) {
    if ($j === 1) {
      $timestamp = time() + 9 * 60 * 60 + 10 * 24 * 60 * 60; // JST
    }
    $yyyy = date('Y', $timestamp);
    $mm = date('m', $timestamp);

    $res = $mu_->get_contents('https://eco.mtk.nao.ac.jp/koyomi/dni/' . $yyyy . '/m' . getenv('AREA_ID') . $mm . '.html', NULL, TRUE);

    $tmp = explode('<table ', $res);
    $tmp = explode('</table>', $tmp[1]);
    $tmp = explode('</tr>', $tmp[0]);
    array_shift($tmp);
    array_pop($tmp);

    $dt = date('Y-m-', $timestamp) . '01';

    for ($i = 0; $i < count($tmp); $i++) {
      $timestamp = strtotime("${dt} +${i} day"); // UTC
      $rc = preg_match('/.+<td>(.+?)</', $tmp[$i], $matches);
      $list_moon_age[$timestamp] = '☽' . trim($matches[1]);
    }
  }
  $list_moon_age = $mu_->to_small_size($list_moon_age);
  error_log(getmypid() . ' [' . __METHOD__ . '] $list_moon_age : ' . print_r($list_moon_age, TRUE));

  return $list_moon_age;
}
?>
