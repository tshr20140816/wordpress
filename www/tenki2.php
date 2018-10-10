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

if (count($list_weather) == 0) {
  error_log('WEATHER DATA NONE');
  exit();
}

// Access Token

$connection_info = parse_url(getenv('DATABASE_URL'));
$pdo = new PDO(
  "pgsql:host=${connection_info['host']};dbname=" . substr($connection_info['path'], 1),
  $connection_info['user'],
  $connection_info['pass']);

$sql = <<< __HEREDOC__
SELECT M1.access_token
      ,M1.refresh_token
      ,M1.expires_in
      ,M1.create_time
      ,M1.update_time
      ,CASE WHEN LOCALTIMESTAMP < M1.update_time + interval '90 minutes' THEN 0 ELSE 1 END refresh_flag
  FROM m_authorization M1;
__HEREDOC__;

$access_token = NULL;
foreach ($pdo->query($sql) as $row) {
  $access_token = $row['access_token'];
  $refresh_token = $row['refresh_token'];
  $refresh_flag = $row['refresh_flag'];
}

if ($access_token == NULL) {
  error_log('ACCESS TOKEN NONE');
  $pdo = null;
  exit();
}
$pdo = null;

// Get Tasks

error_log('ACCESS TOKEN : ' . $access_token);
$res = get_contents('https://api.toodledo.com/3/tasks/get.php?access_token=' . $access_token . '&comp=0&fields=tag', NULL);
// error_log($res);

$tasks = json_decode($res, TRUE);
error_log(print_r($tasks, TRUE));
$list_delete_task = [];
for ($i = 0; $i < count($tasks); $i++) {
  if (array_key_exists('id', $tasks[$i]) && array_key_exists('tag', $tasks[$i])) {
    if ($tasks[$i]['tag'] == 'WEATHER2') {
      $list_delete_task[] = $tasks[$i]['id'];
      error_log('DELETE TARGET TASK ID : ' . $tasks[$i]['id']);
      if (count($list_delete_task) == 50) {
        break;
      }
    }
  }
}

// Get Folders

$res = get_contents('https://api.toodledo.com/3/folders/get.php?access_token=' . $access_token, NULL);
$folders = json_decode($res, TRUE);

$weather_folder_id = 0;
for ($i = 0; $i < count($folders); $i++) {
  if ($folders[$i]['name'] == 'WEATHER') {
    $weather_folder_id = $folders[$i]['id'];
    error_log('WEATHER FOLDER ID : ' . $weather_folder_id);
    break;
  }
}

// Add Tasks

$tmp = implode(',', $list_weather);
$tmp = str_replace('__FOLDER_ID__', $weather_folder_id, $tmp);
$post_data = ['access_token' => $access_token, 'tasks' => '[' . $tmp . ']'];

// error_log(http_build_query($post_data));

$res = get_contents(
  'https://api.toodledo.com/3/tasks/add.php',
  [CURLOPT_POST => TRUE,
   CURLOPT_POSTFIELDS => http_build_query($post_data),
  ]);

error_log('add.php RESPONSE : ' . $res);

// Delete Tasks

error_log('DELETE TARGET TASK COUNT : ' . count($list_delete_task));

if (count($list_delete_task) > 0) {
  $post_data = ['access_token' => $access_token, 'tasks' => '[' . implode(',', $list_delete_task) . ']'];  
  $res = get_contents(
    'https://api.toodledo.com/3/tasks/delete.php',
    [CURLOPT_POST => TRUE,
     CURLOPT_POSTFIELDS => http_build_query($post_data),
    ]);
  error_log('delete.php RESPONSE : ' . $res);
}

exit();

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
