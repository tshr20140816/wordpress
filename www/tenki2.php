<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s'));

const LIST_YOBI = array('日', '月', '火', '水', '木', '金', '土');

$mu = new MyUtils();

// Access Token
$access_token = $mu->get_access_token();

// Get Contexts
$list_context_id = $mu->get_contexts();

// Get Folders
$folder_id_label = $mu->get_folder_id('LABEL');

// holiday 今月含み4ヶ月分
$list_holiday = get_holiday($mu);
error_log($pid . ' $list_holiday : ' . print_r($list_holiday, TRUE));

// 24sekki 今年と来年分
$list_24sekki = get_24sekki($mu);
error_log($pid . ' $list_24sekki : ' . print_r($list_24sekki, TRUE));

// Sun 今月含み4ヶ月分
$list_sunrise_sunset = get_sun($mu);
error_log($pid . ' $list_sunrise_sunset : ' . print_r($list_sunrise_sunset, TRUE));

// Weather Information 今日の10日後から70日分

$list_base = [];
for ($i = 0; $i < 12; $i++) {
  $url = 'https://feed43.com/' . getenv('SUB_ADDRESS') . ($i * 5 + 11) . '-' . ($i * 5 + 15) . '.xml';
  $res = $mu->get_contents($url);
  foreach (explode("\n", $res) as $one_line) {
    if (strpos($one_line, '<title>_') !== FALSE) {
      $tmp = explode('_', $one_line);
      $tmp1 = explode(' ', $tmp[2]);
      $tmp2 = explode('/', $tmp1[1]);
      // 10月から4月までの閾値は30、その他は38
      if ((int)$tmp2[0] > ((int)substr($tmp1[0], 0, 1) < 5 ? 30 : 38)) {
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
// To Small Size
$update_marker = $mu->to_small_size(' _' . date('ymd') . '_');
for ($i = 0; $i < 70; $i++) {
  $timestamp = strtotime(date('Y-m-d') . ' +' . ($i + 10) . ' days');
  $dt = date('n/j', $timestamp);
  // error_log($pid . ' $dt : ' . $dt);
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
    . ',"folder":' . $folder_id_label . '}';
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

// file_put_contents('/tmp/tasks_tenki2', serialize($tasks));

$list_delete_task = [];
for ($i = 0; $i < count($tasks); $i++) {
  // error_log($pid . ' ' . $i . ' ' . print_r($tasks[$i], TRUE));
  if (array_key_exists('id', $tasks[$i]) && array_key_exists('tag', $tasks[$i])) {
    if ($tasks[$i]['tag'] == 'WEATHER2'
        || $tasks[$i]['tag'] == 'SOCCER'
        || $tasks[$i]['tag'] == 'CULTURECENTER'
        || $tasks[$i]['tag'] == 'HIGHWAY') {
      $list_delete_task[] = $tasks[$i]['id'];
    } else if ($tasks[$i]['tag'] == 'HOLIDAY' || $tasks[$i]['tag'] == 'ADDITIONAL') {
      if (array_key_exists(date('Ymd', $tasks[$i]['duedate']), $list_add_task)) {
        $list_delete_task[] = $tasks[$i]['id'];
      }
    }
  }
}
error_log($pid . ' $list_delete_task : ' . print_r($list_delete_task, TRUE));

// Sun Tasks
$list_add_task = array_merge($list_add_task, get_task_sun($mu));

// Moon Tasks
$list_add_task = array_merge($list_add_task, get_task_moon($mu));

// High Way Tasks
$list_add_task = array_merge($list_add_task, get_task_highway($mu));

// Soccer Tasks
$list_add_task = array_merge($list_add_task, get_task_soccer($mu));

// Culture Center Tasks
$list_add_task = array_merge($list_add_task, get_task_culturecenter($mu));

error_log($pid . ' $list_add_task : ' . print_r($list_add_task, TRUE));

// Add Tasks
$rc = $mu->add_tasks($list_add_task);

// Delete Tasks
$mu->delete_tasks($list_delete_task);

error_log("${pid} FINISH");

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

  $res = $mu_->get_contents($url, NULL, TRUE);
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

function get_sun($mu_) {
  // Sun 今月含み4ヶ月分

  $list_sunrise_sunset = [];

  for ($j = 0; $j < 4; $j++) {
    $timestamp = strtotime(date('Y-m-01') . " +${j} month");
    $yyyy = date('Y', $timestamp);
    $mm = date('m', $timestamp);
    error_log($pid . ' $yyyy : ' . $yyyy);
    error_log($pid . ' $mm : ' . $mm);
    $res = $mu_->get_contents('https://eco.mtk.nao.ac.jp/koyomi/dni/' . $yyyy . '/s' . getenv('AREA_ID') . $mm . '.html', NULL, TRUE);

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
  $list_sunrise_sunset = $mu_->to_small_size($list_sunrise_sunset);

  return $list_sunrise_sunset;
}

function get_task_soccer($mu_) {

  // Get Folders
  $folder_id_private = $mu_->get_folder_id('PRIVATE');

  // Get Contexts
  $list_context_id = $mu_->get_contexts();

  $res = $mu_->get_contents(getenv('SOCCER_TEAM_CSV_FILE'));
  $res = mb_convert_encoding($res, 'UTF-8', 'SJIS');

  $list_tmp = explode("\n", $res);

  $list_add_task = [];
  $add_task_template = '{"title":"__TITLE__","duedate":"__DUEDATE__","context":"__CONTEXT__","tag":"SOCCER","folder":"'
    . $folder_id_private . '"}';
  for ($i = 1; $i < count($list_tmp) - 1; $i++) {
    $tmp = explode(',', $list_tmp[$i]);
    $timestamp = strtotime(trim($tmp[1], '"'));
    if (date('Ymd') >= date('Ymd', $timestamp)) {
      continue;
    }

    $tmp1 = trim($tmp[2], '"');
    $rc = preg_match('/\d+:\d+:\d\d/', $tmp1);
    if ($rc == 1) {
      $tmp1 = substr($tmp1, 0, strlen($tmp1) - 3);
    }
    $tmp1 = substr(trim($tmp[1], '"'), 5) . ' ' . $tmp1 . ' ' . trim($tmp[0], '"') . ' ' . trim($tmp[6], '"');

    $tmp1 = str_replace('__TITLE__', $tmp1, $add_task_template);
    $tmp1 = str_replace('__DUEDATE__', $timestamp, $tmp1);
    $tmp1 = str_replace('__CONTEXT__', $list_context_id[date('w', $timestamp)], $tmp1);
    $list_add_task[] = $tmp1;
  }
  $count_task = count($list_add_task);
  $list_add_task[] = '{"title":"' . date('Y/m/d H:i:s', strtotime('+ 9 hours')) . '  Soccer Task Add : ' . $count_task
    . '","context":"' . $list_context_id[date('w', mktime(0, 0, 0, 1, 4, 2018))]
    . '","duedate":"' . mktime(0, 0, 0, 1, 4, 2018) . '","folder":"' . $folder_id_private . '"}';
  error_log(getmypid() . ' TASKS SOCCER : ' . print_r($list_add_task, TRUE));

  return $list_add_task;
}

function get_task_culturecenter($mu_) {

  // Get Folders
  $folder_id_private = $mu_->get_folder_id('PRIVATE');

  // Get Contexts
  $list_context_id = $mu_->get_contexts();

  $y = date('Y');
  $m = date('n');

  $list_add_task = [];
  for ($j = 0; $j < 2; $j++) {
    $url = 'http://www.cf.city.hiroshima.jp/saeki-cs/sche6_park/sche6.cgi?year=' . $y . '&mon=' . $m;

    $res = $mu_->get_contents($url);
    $res = mb_convert_encoding($res, 'UTF-8', 'SJIS');

    $tmp = explode('<col span=1 align=right>', $res);
    $tmp = explode('</table>', $tmp[1]);

    $rc = preg_match_all('/<tr .+?<b>(.+?)<.*?<td(.*?)<\/td><\/tr>/s', $tmp[0], $matches, PREG_SET_ORDER);

    for ($i = 0; $i < count($matches); $i++) {
      $timestamp = mktime(0, 0, 0, $m, $matches[$i][1], $y);
      if (date('Ymd') > date('Ymd', $timestamp)) {
        continue;
      }
      $tmp = $matches[$i][2];
      $tmp = preg_replace('/<font .+?>.+?>/', '', $tmp);
      $tmp = preg_replace('/bgcolor.+?>/', '', $tmp);
      $tmp = trim($tmp, " \t\n\r\0\t>");
      $tmp = str_replace('　', '', $tmp);
      $tmp = trim(str_replace('<br>', ' ', $tmp));
      if (strlen($tmp) == 0) {
        continue;
      }
      $list_add_task[] = '{"title":"' . date('m/d', $timestamp) . ' 文セ ★ ' . $tmp
        . '","duedate":"' . $timestamp
      . '","context":"' . $list_context_id[date('w', $timestamp)]
      . '","tag":"CULTURECENTER","folder":"' . $folder_id_private . '"}';
    }
    if ($m == 12) {
      $yyyy++;
      $m = 1;
    } else {
      $m++;
    }
  }
  $count_task = count($list_add_task);
  $list_add_task[] = '{"title":"' . date('Y/m/d H:i:s', strtotime('+ 9 hours')) . '  Culture Center Task Add : ' . $count_task
    . '","context":"' . $list_context_id[date('w', mktime(0, 0, 0, 1, 4, 2018))]
    . '","duedate":"' . mktime(0, 0, 0, 1, 4, 2018) . '","folder":"' . $folder_id_private . '"}';
  error_log(getmypid() . ' TASKS CULTURECENTER : ' . print_r($list_add_task, TRUE));

  return $list_add_task;
}

function get_task_highway($mu_) {

  // Get Folders
  $folder_id_private = $mu_->get_folder_id('PRIVATE');

  // Get Contexts
  $list_context_id = $mu_->get_contexts();

  $url = 'https://www.w-nexco.co.jp/traffic_info/construction/traffic.php?fdate='
    . date('Ymd', strtotime('+1 day'))
    . '&tdate='
    . date('Ymd', strtotime('+14 day'))
    . '&ak=1&ac=1&kisei%5B%5D=901&dirc%5B%5D=1&dirc%5B%5D=2&order=2&ronarrow=1'
    . '&road%5B%5D=1011&road%5B%5D=1912&road%5B%5D=1020&road%5B%5D=225A&road%5B%5D=1201'
    . '&road%5B%5D=1222&road%5B%5D=1231&road%5B%5D=234D&road%5B%5D=1232&road%5B%5D=1260';

  $res = $mu_->get_contents($url);

  $tmp = explode('<!--工事日程順-->', $res);
  $tmp = explode('<table cellspacing="0" summary="" class="lb05">', $tmp[0]);
  $tmp = explode('<th>備考</th>', $tmp[1]);

  $rc = preg_match_all('/<tr.*?>' . str_repeat('.*?<td.*?>(.+?)<\/td>', 5) . '.+?<\/tr>/s', $tmp[1], $matches, PREG_SET_ORDER);

  $list_add_task = [];
  $add_task_template = '{"title":"__TITLE__","duedate":"__DUEDATE__","context":"__CONTEXT__","tag":"HIGHWAY","folder":"'
    . $folder_id_private . '"}';
  for ($i = 0; $i < count($matches); $i++) {
    $yyyy = (int)date('Y');
    $tmp = explode('日', $matches[$i][4]);
    $tmp = explode('月', $tmp[0]);
    if (date('m') == '12' && (int)$tmp[0] == 1) {
      $yyyy++;
    }
    $timestamp = mktime(0, 0, 0, $tmp[0], $tmp[1], $yyyy);

    $tmp = $matches[$i];
    $tmp = date('m/d', $timestamp) . ' ★ ' . $tmp[4] . ' ' . $tmp[2] . ' ' . $tmp[3] . ' ' . $tmp[5] . ' ' . $tmp[1];
    $tmp = str_replace('__TITLE__', $tmp, $add_task_template);
    $tmp = str_replace('__DUEDATE__', $timestamp, $tmp);
    $tmp = str_replace('__CONTEXT__', $list_context_id[date('w', $timestamp)], $tmp);
    $list_add_task[] = $tmp;
  }

  error_log(getmypid() . ' TASKS HIGHWAY : ' . print_r($list_add_task, TRUE));

  return $list_add_task;
}

function get_task_sun($mu_) {
  
  // Get Folders
  $folder_id_label = $mu_->get_folder_id('LABEL');
  // Get Contexts
  $list_context_id = $mu_->get_contexts();
  
  $timestamp = strtotime('+1 day');
  $yyyy = date('Y', $timestamp);
  $mm = date('m', $timestamp);

  $res = $mu_->get_contents('https://eco.mtk.nao.ac.jp/koyomi/dni/' . $yyyy . '/s' . getenv('AREA_ID') . $mm . '.html');
  $res = mb_convert_encoding($res, 'UTF-8', 'EUC-JP');

  $tmp = explode('<table ', $res);
  $tmp = explode('</table>', $tmp[1]);
  $tmp = explode('</tr>', $tmp[0]);
  array_shift($tmp);
  array_pop($tmp);
  
  $list_add_task = [];
  $add_task_template = '{"title":"__TITLE__","duedate":"__DUEDATE__","context":"__CONTEXT__","tag":"WEATHER2","folder":"'
    . $folder_id_label . '"}';
  for ($i = 0; $i < count($tmp); $i++) {
    $rc = preg_match('/<tr><td.*?>' . substr(' ' . date('j', $timestamp), -2) . '<\/td>/', $tmp[$i]);
    if ($rc == 1) {
      $rc = preg_match('/.+?<\/td>.*?<td>(.+?)<\/td>.*?<td>.+?<\/td>.*?<td>.+?<\/td>.*?<td>.+?<\/td>.*?<td>(.+?)</', $tmp[$i], $matches);
      
      $tmp = date('m/d', $timestamp) . ' 0' . trim($matches[1]) . ' 日の出';
      $tmp = str_replace('__TITLE__', $tmp, $add_task_template);
      $tmp = str_replace('__DUEDATE__', $timestamp, $tmp);
      $tmp = str_replace('__CONTEXT__', $list_context_id[date('w', $timestamp)], $tmp);
      $list_add_task[] = $tmp;
      
      $tmp = date('m/d', $timestamp) . ' ' . trim($matches[2]) . ' 日の入り';
      $tmp = str_replace('__TITLE__', $tmp, $add_task_template);
      $tmp = str_replace('__DUEDATE__', $timestamp, $tmp);
      $tmp = str_replace('__CONTEXT__', $list_context_id[date('w', $timestamp)], $tmp);
      $list_add_task[] = $tmp;
      break;
    }
  }
  error_log(getmypid() . ' SUN : ' . print_r($list_add_task, TRUE));
  return $list_add_task;
}

function get_task_moon($mu_) {

  // Get Folders
  $folder_id_label = $mu_->get_folder_id('LABEL');
  // Get Contexts
  $list_context_id = $mu_->get_contexts();

  $timestamp = strtotime('+1 day');
  $yyyy = date('Y', $timestamp);
  $mm = date('m', $timestamp);

  $res = $mu_->get_contents('https://eco.mtk.nao.ac.jp/koyomi/dni/' . $yyyy . '/m' . getenv('AREA_ID') . $mm . '.html');

  $res = mb_convert_encoding($res, 'UTF-8', 'EUC-JP');

  $tmp = explode('<table ', $res);
  $tmp = explode('</table>', $tmp[1]);
  $tmp = explode('</tr>', $tmp[0]);
  array_shift($tmp);
  array_pop($tmp);
      
  $list_add_task = [];
  $add_task_template = '{"title":"__TITLE__","duedate":"__DUEDATE__","context":"__CONTEXT__","tag":"WEATHER2","folder":"'
    . $folder_id_label . '"}';
  for ($i = 0; $i < count($tmp); $i++) {
    $rc = preg_match('/<tr><td.*?>' . substr(' ' . date('j', $timestamp), -2) . '<\/td>/', $tmp[$i]);
    if ($rc == 1) {
      $rc = preg_match('/.+?<\/td>.*?<td>(.+?)<\/td>.*?<td>.+?<\/td>.*?<td>.+?<\/td>.*?<td>.+?<\/td>.*?<td>(.+?)</', $tmp[$i], $matches);

      if (trim($matches[1]) != '--:--') {
        $tmp = date('m/d', $timestamp) . ' ' . substr('0' . trim($matches[1]), -5) . ' 月の出';
        $tmp = str_replace('__TITLE__', $tmp, $add_task_template);
        $tmp = str_replace('__DUEDATE__', $timestamp, $tmp);
        $tmp = str_replace('__CONTEXT__', $list_context_id[date('w', $timestamp)], $tmp);
        $list_add_task[] = $tmp;
      }
      
      if (trim($matches[2]) != '--:--') {
        $tmp = date('m/d', $timestamp) . ' ' . substr('0' . trim($matches[2]), -5) . ' 月の入り';
        $tmp = str_replace('__TITLE__', $tmp, $add_task_template);
        $tmp = str_replace('__DUEDATE__', $timestamp, $tmp);
        $tmp = str_replace('__CONTEXT__', $list_context_id[date('w', $timestamp)], $tmp);
        $list_add_task[] = $tmp;
      }
      break;
    }
  }
  error_log(getmypid() . ' MOON : ' . print_r($list_add_task, TRUE));
  return $list_add_task;
}
?>
