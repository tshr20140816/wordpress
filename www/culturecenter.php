<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi}");

$mu = new MyUtils();

// Access Token
$access_token = $mu->get_access_token();

// Get Contexts
$list_context_id = $mu->get_contexts();

// Get Folders
$private_folder_id = $mu->get_folder_id('PRIVATE');

// Get Tasks

$tasks = [];
$file_name = '/tmp/tasks_tenki2';
if (file_exists($file_name)) {
  $timestamp = filemtime($file_name);
  if ($timestamp > strtotime('-5 minutes')) {
    $tasks = unserialize(file_get_contents($file_name));
    error_log($pid . ' CACHE HIT TASKS');
  }
}

if (count($tasks) == 0) {
  $url = 'https://api.toodledo.com/3/tasks/get.php?comp=0&fields=tag&access_token=' . $access_token
    . '&after=' . strtotime('-2 day');
  $res = $mu->get_contents($url);

  $tasks = json_decode($res, TRUE);
}

$list_delete_task = [];
for ($i = 0; $i < count($tasks); $i++) {
  if (array_key_exists('id', $tasks[$i]) && array_key_exists('tag', $tasks[$i])) {
    if ($tasks[$i]['tag'] == 'CULTURECENTER') {
      $list_delete_task[] = $tasks[$i]['id'];
    }
  }
}
error_log($pid . ' $list_delete_task : ' . print_r($list_delete_task, TRUE));

// Culture Center

$y = date('Y');
$m = date('n');

$list_library = [];
for ($j = 0; $j < 2; $j++) {
  $url = 'http://www.cf.city.hiroshima.jp/saeki-cs/sche6_park/sche6.cgi?year=' . $y . '&mon=' . $m;
  error_log($pid . ' $url : ' . $url);
  
  $res = $mu->get_contents($url);
  $res = mb_convert_encoding($res, 'UTF-8', 'SJIS');

  // error_log($res);

  $tmp = explode('<col span=1 align=right>', $res);
  $tmp = explode('</table>', $tmp[1]);

  // error_log($tmp[0]);

  $rc = preg_match_all('/<tr .+?<b>(.+?)<.*?<td(.*?)<\/td><\/tr>/s', $tmp[0], $matches, PREG_SET_ORDER);
  // error_log(print_r($matches, TRUE));

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
    // error_log($tmp);
    $tmp = trim(str_replace('<br>', ' ', $tmp));
    if (strlen($tmp) == 0) {
      continue;
    }
    $list_library[] = '{"title":"' . date('m/d', $timestamp) . ' 文セ ★ ' . $tmp
      . '","duedate":"' . $timestamp
    . '","context":"' . $list_context_id[date('w', $timestamp)]
    . '","tag":"CULTURECENTER","folder":"' . $private_folder_id . '"}';
  }
  if ($m == 12) {
    $yyyy++;
    $m = 1;
  } else {
    $m++;
  }
}
error_log($pid . ' $list_library : ' . print_r($list_library, TRUE));

// Add Tasks

$post_data = ['access_token' => $access_token, 'tasks' => '[' . implode(',', $list_library) . ']'];

// error_log(http_build_query($post_data));

$res = $mu->get_contents(
  'https://api.toodledo.com/3/tasks/add.php',
  [CURLOPT_POST => TRUE,
   CURLOPT_POSTFIELDS => http_build_query($post_data),
  ]);

error_log("${pid} add.php RESPONSE : ${res}");

$count_add = substr_count($res, '"completed":0');

$tmp = '[{"title":"' . date('Y/m/d H:i:s', strtotime('+ 9 hours')) . ' ' . $requesturi . " Add : " . $count_add
  . '","duedate":"' . mktime(0, 0, 0, 1, 1, 2018). '"}]';
$post_data = ['access_token' => $access_token, 'tasks' => $tmp];

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
  'https://' . getenv('HEROKU_APP_NAME') . '.herokuapp.com/quota.php',
  [CURLOPT_USERPWD => getenv('BASIC_USER') . ':' . getenv('BASIC_PASSWORD'),
  ]);

exit();
?>
