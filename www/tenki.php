<?php

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
  exit();
}

if ($refresh_flag == 1) {
  error_log('refresh_token : ' . $refresh_token);
  $post_data = ['grant_type' => 'refresh_token', 'refresh_token' => $refresh_token];
  
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, 'https://api.toodledo.com/3/account/token.php'); 
  curl_setopt($ch, CURLOPT_USERPWD, getenv('TOODLEDO_CLIENTID') . ':' . getenv('TOODLEDO_SECRET'));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_POST, TRUE);
  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
  $res = curl_exec($ch);
  curl_close($ch);
  
  error_log($res);
  $params = json_decode($res, TRUE);
  
  $sql = <<< __HEREDOC__
UPDATE m_authorization
   SET access_token = :b_access_token
      ,refresh_token = :b_refresh_token;
      ,update_time = LOCALTIMESTAMP;
__HEREDOC__;
  
  $statement = $pdo->prepare($sql);
  $rc = $statement->execute([':b_access_token' => $params['access_token'],
                             ':b_refresh_token' => $params['refresh_token']]);
  error_log('UPDATE RESULT : ' . $rc);
  
  $access_token = $params['access_token'];
}

$pdo = null;

// Weather Information

$res = file_get_contents('https://tenki.jp/week/' . getenv('LOCATION_NUMBER') . '/');

$rc = preg_match('/announce_datetime:(\d+-\d+-\d+) (\d+)/', $res, $matches);

error_log($matches[0]);
error_log($matches[1]);
error_log($matches[2]);

$dt = $matches[1];
$update_marker = ' __' . substr($matches[1], 8) . $matches[2] . '__';

$subscript = '₀₁₂₃₄₅₆₇₈₉';
for ($i = 0; $i < 10; $i++) {
  $update_marker = str_replace($i, mb_substr($subscript, $i, 1), $update_marker);
}
/*
$update_marker = str_replace('0', '₀', $update_marker);
$update_marker = str_replace('1', '₁', $update_marker);
$update_marker = str_replace('2', '₂', $update_marker);
$update_marker = str_replace('3', '₃', $update_marker);
$update_marker = str_replace('4', '₄', $update_marker);
$update_marker = str_replace('5', '₅', $update_marker);
$update_marker = str_replace('6', '₆', $update_marker);
$update_marker = str_replace('7', '₇', $update_marker);
$update_marker = str_replace('8', '₈', $update_marker);
$update_marker = str_replace('8', '₉', $update_marker);
*/

$tmp = explode(getenv('POINT_NAME'), $res);
$tmp = explode('<td class="forecast-wrap">', $tmp[1]);
$list_yobi = array('日', '月', '火', '水', '木', '金', '土');
$list_weather = [];
for ($i = 0; $i < 10; $i++) {
  $list = explode("\n", str_replace(' ', '', trim(strip_tags($tmp[$i + 1]))));
  $tmp2 = $list[0];
  $tmp2 = str_replace('晴', '☀', $tmp2);
  $tmp2 = str_replace('曇', '☁', $tmp2);
  $tmp2 = str_replace('雨', '☂', $tmp2);
  $tmp2 = str_replace('のち', '/', $tmp2);
  $tmp2 = str_replace('時々', '|', $tmp2);
  $tmp2 = str_replace('一時', '|', $tmp2);
  error_log('##### ' . $list_yobi[date('w', strtotime($dt . ' +' . $i . ' day'))] . '曜日 ' . date('m/d', strtotime($dt . ' +' . $i . ' day')) . ' ##### ' . $tmp2 . ' ' . $list[2] . ' ' . $list[1] . $update_marker);
  $list_weather[] = '{"title":"' . '##### ' . $list_yobi[date('w', strtotime($dt . ' +' . $i . ' day'))] . '曜日 ' . date('m/d', strtotime($dt . ' +' . $i . ' day')) . ' ##### ' . $tmp2 . ' ' . $list[2] . ' ' . $list[1] . $update_marker .'","duedate":"' . strtotime($dt . ' +' . $i . ' day') . '","tag":"WEATHER"}';
}

if (count($list_weather) == 0) {
  exit();
}

// Get Tasks

$res = file_get_contents('https://api.toodledo.com/3/tasks/get.php?access_token=' . $access_token . '&comp=0&fields=tag');
// error_log($res);

$tasks = json_decode($res, TRUE);
// error_log(print_r($tasks, TRUE));
$list_delete_task = [];
for ($i = 0; $i < count($tasks); $i++) {
  if (array_key_exists('id', $tasks[$i]) && array_key_exists('tag', $tasks[$i])) {
    if ($tasks[$i]['tag'] == 'WEATHER') {
      $list_delete_task[] = $tasks[$i]['id'];
      error_log($tasks[$i]['id']);
      if (count($list_delete_task) == 50) {
        break;
      }
    }
  }
}

// Delete Tasks

error_log('DELETE TARGET TASK COUNT : ' . count($list_delete_task));

if (count($list_delete_task) > 0) {
  $post_data = ['access_token' => $access_token, 'tasks' => '[' . implode(',', $list_delete_task) . ']'];
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, 'https://api.toodledo.com/3/tasks/delete.php'); 
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_POST, TRUE);
  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
  $res = curl_exec($ch);
  curl_close($ch);
  error_log($res);
}

// Get Folders

$res = file_get_contents('https://api.toodledo.com/3/folders/get.php?access_token=' . $access_token);
$folders = json_decode($res, TRUE);

$weather_folder_id = 0;
for ($i = 0; $i < count($folders); $i++) {
  if ($folders[$i]['name'] == 'WEATHER') {
    $weather_folder_id = $folders[$i]['id'];
    break;
  }
}

// Add Tasks

$tmp = implode(',', $list_weather);
$tmp = str_replace('"tag":"WEATHER"', '"tag":"WEATHER","folder":"' . $weather_folder_id . '"', $tmp);
$post_data = ['access_token' => $access_token, 'tasks' => '[' . $tmp . ']'];

// error_log(http_build_query($post_data));

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.toodledo.com/3/tasks/add.php'); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_POST, TRUE);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
$res = curl_exec($ch);
curl_close($ch);

error_log($res);

exit();

?>
