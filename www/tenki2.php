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

// Get Folders
$label_folder_id = $mu->get_folder_id('LABEL');

// holiday 今月含み4ヶ月分
$list_holiday = get_holiday($mu);  
error_log($pid . ' $list_holiday : ' . print_r($list_holiday, TRUE));

// 24sekki 今年と来年分
$list_24sekki = get_24sekki($mu);  
error_log($pid . ' $list_24sekki : ' . print_r($list_24sekki, TRUE));

// Sun 今月含み4ヶ月分

$list_sunrise_sunset = [];

for ($j = 0; $j < 4; $j++) {
  $timestamp = strtotime(date('Y-m-01') . " +${j} month");
  $yyyy = date('Y', $timestamp);
  $mm = date('m', $timestamp);
  error_log($pid . ' $yyyy : ' . $yyyy);
  error_log($pid . ' $mm : ' . $mm);
  $res = $mu->get_contents('https://eco.mtk.nao.ac.jp/koyomi/dni/' . $yyyy . '/s' . getenv('AREA_ID') . $mm . '.html');
  
  $tmp = explode('<table ', $res);
  $tmp = explode('</table>', $tmp[1]);
  $tmp = explode('</tr>', $tmp[0]);
  array_shift($tmp);
  array_pop($tmp);

  $dt = date('Y-m-01', $timestamp);

  for ($i = 0; $i < count($tmp); $i++) {
    $timestamp = strtotime("${dt} +${i} day"); // UTC
    $rc = preg_match('/.+?<\/td>.*?<td>(.+?)<\/td>.*?<td>.+?<\/td>.*?<td>.+?<\/td>.*?<td>.+?<\/td>.*?<td>(.+?)</', $tmp[$i], $matches);
    // error_log(trim($matches[1]));
    $list_sunrise_sunset[$timestamp] = '↗' . trim($matches[1]) . ' ↘' . trim($matches[2]);
  }
}
// To Small Size
$subscript = '₀₁₂₃₄₅₆₇₈₉';
for ($i = 0; $i < 10; $i++) {
  $list_sunrise_sunset = str_replace($i, mb_substr($subscript, $i, 1), $list_sunrise_sunset);
}
error_log($pid . ' $list_sunrise_sunset : ' . print_r($list_sunrise_sunset, TRUE));

// Weather Information 今日の10日後から70日分

$list_base = [];
for ($i = 0; $i < 12; $i++) {
  $url = 'https://feed43.com/' . getenv('SUB_ADDRESS') . ($i * 5 + 11) . '-' . ($i * 5 + 15) . '.xml';
  $res = $mu->get_contents($url);
  error_log($pid . ' ' . $res);
  foreach (explode("\n", $res) as $one_line) {
    if (strpos($one_line, '<title>_') !== FALSE) {
      // error_log($one_line);
      $tmp = explode('_', $one_line);
      $tmp1 = explode(' ', $tmp[2]);
      $tmp2 = explode('/', $tmp1[1]);
      if ((int)$tmp2[0] > 38) {
        // 華氏 → 摂氏
        $tmp2[0] = (int)(((int)$tmp2[0] - 32) * 5 / 9);
        $tmp2[1] = (int)(((int)$tmp2[1] - 32) * 5 / 9);
        $tmp[2] = $tmp1[0] . ' ' . $tmp2[0] . '/' . $tmp2[1];
      }
      $list_base[$tmp[1]] = $tmp[2];
    }
  }
}
error_log($pid . ' $list_base : ' . print_r($list_base, TRUE));

$list_add_task = [];
$update_marker = ' _' . date('ymd') . '_';
// To Small Size
$subscript = '₀₁₂₃₄₅₆₇₈₉';
for ($i = 0; $i < 10; $i++) {
  $update_marker = str_replace($i, mb_substr($subscript, $i, 1), $update_marker);
}
for ($i = 0; $i < 70; $i++) {
  $timestamp = strtotime(date('Y-m-d') . ' +' . ($i + 10) . ' days');
  $dt = date('n/j', $timestamp);
  error_log($pid . ' $dt : ' . $dt);
  if (array_key_exists($dt, $list_base)) {
    $tmp = $list_base[$dt];
  } else {
    $tmp = '----';
  }
  // 30日後以降は土日月及び祝祭日、24節気のみ
  if ($i > 20 && (date('w', $timestamp) + 1) % 7 > 2
      && !array_key_exists($timestamp, $list_holiday)
      && !array_key_exists($timestamp, $list_24sekki)) {
    continue;
  }
  $tmp = '### ' . LIST_YOBI[date('w', $timestamp)] . '曜日 ' . date('m/d', $timestamp) . ' ### ' . $tmp . $update_marker;
  if (array_key_exists($timestamp, $list_holiday)) {
    $tmp = str_replace(' ###', ' ★' . $list_holiday[$timestamp] . '★ ###', $tmp);
  }
  if (array_key_exists($timestamp, $list_24sekki)) {
    $tmp .= ' ' . $list_24sekki[$timestamp];
  }
  if (array_key_exists($timestamp, $list_sunrise_sunset)) {
    $tmp .= ' ' . $list_sunrise_sunset[$timestamp];
  }
  $list_add_task[date('Ymd', $timestamp)] = '{"title":"' . $tmp
    . '","duedate":"' . $timestamp
    . '","tag":"WEATHER2","context":' . $list_context_id[date('w', $timestamp)]
    . ',"folder":' . $label_folder_id . '}';
}
error_log($pid . ' $list_add_task : ' . print_r($list_add_task, TRUE));

if (count($list_add_task) == 0) {
  error_log($pid . ' WEATHER DATA NONE');
  exit();
}

// heroku-buildpack-php

$file_name_current = '/tmp/current_version';
$file_name_latest = '/tmp/latest_version';

if (file_exists($file_name_current) && file_exists($file_name_latest)) {
  $current_version = trim(file_get_contents($file_name_current), '"');
  $latest_version = trim(file_get_contents($file_name_latest), '"');
  error_log($pid . ' heroku-buildpack-php current : ' . $current_version);
  error_log($pid . ' heroku-buildpack-php latest : ' . $latest_version);
  if ($current_version != $latest_version) {
    $list_add_task[date('Ymd')] = '{"title":"heroku-buildpack-php : update ' . $latest_version
      . '","duedate":"' . mktime(0, 0, 0, 1, 1, 2018)
      . '","context":' . $list_context_id[date('w', mktime(0, 0, 0, 1, 1, 2018))] . '}';
  }
}

// Get Tasks

$url = 'https://api.toodledo.com/3/tasks/get.php?comp=0&fields=tag,folder,duedate&access_token=' . $access_token
  . '&after=' . strtotime('-2 day');
$res = $mu->get_contents($url);
// error_log($res);

$tasks = json_decode($res, TRUE);
// error_log($pid . ' $tasks : ' . print_r($tasks, TRUE));

file_put_contents('/tmp/tasks_tenki2', serialize($tasks));

$list_delete_task = [];
for ($i = 0; $i < count($tasks); $i++) {
  // error_log($pid . ' ' . $i . ' ' . print_r($tasks[$i], TRUE));
  if (array_key_exists('id', $tasks[$i]) && array_key_exists('tag', $tasks[$i])) {
    if ($tasks[$i]['tag'] == 'WEATHER2') {
      $list_delete_task[] = $tasks[$i]['id'];
    } else if ($tasks[$i]['tag'] == 'HOLIDAY' || $tasks[$i]['tag'] == 'ADDITIONAL') {
      if (array_key_exists(date('Ymd', $tasks[$i]['duedate']), $list_add_task)) {
        $list_delete_task[] = $tasks[$i]['id'];
      }
    }
  }
}
error_log($pid . ' $list_delete_task : ' . print_r($list_delete_task, TRUE));

// Add Tasks
$list_add_task = str_replace('__FOLDER_ID__', $label_folder_id, $list_add_task);
$rc = $mu->add_tasks($list_add_task);

// Delete Tasks
$mu->delete_tasks($list_delete_task);

error_log("${pid} FINISH");

$res = $mu->get_contents(
  'https://' . getenv('HEROKU_APP_NAME') . '.herokuapp.com/culturecenter.php',
  [CURLOPT_USERPWD => getenv('BASIC_USER') . ':' . getenv('BASIC_PASSWORD'),
  ]);

exit();

function get_holiday($mu_) {
  // holiday 今月含み4ヶ月分

  $start_yyyy = date('Y');
  $start_m = date('n');
  $finish_yyyy = date('Y', strtotime('+3 month'));
  $finish_m = date('n', strtotime('+3 month'));

  $url = 'http://calendar-service.net/cal?start_year=' . $start_yyyy
    . '&start_mon=' . $start_m . '&end_year=' . $finish_yyyy . '&end_mon=' . $finish_m
    . '&year_style=normal&month_style=numeric&wday_style=ja_full&format=csv&holiday_only=1&zero_padding=1';

  $res = $mu_->get_contents($url);
  $res = mb_convert_encoding($res, 'UTF-8', 'EUC-JP');

  $tmp = explode("\n", $res);
  array_shift($tmp); // ヘッダ行削除
  array_pop($tmp); // フッタ行(空行)削除

  $list_holiday = [];
  for ($i = 0; $i < count($tmp); $i++) {
    $tmp1 = explode(',', $tmp[$i]);
    $timestamp = mktime(0, 0, 0, $tmp1[1], $tmp1[2], $tmp1[0]);
    $list_holiday[$timestamp] = $tmp1[7];
  }
  
  return $list_holiday;
}

function get_24sekki($mu_) {
  // 24sekki 今年と来年分

  $list_24sekki = [];

  $yyyy = (int)date('Y');
  for ($j = 0; $j < 2; $j++) {
    $post_data = ['from_year' => $yyyy];

    $res = $mu_->get_contents(
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
      error_log($tmp1 . ' ' . $matches[1]);
      $list_24sekki[strtotime($tmp1)] = '【' . $matches[1] . '】';
    }
    $yyyy++;
  }

  return $list_24sekki;
}
?>
