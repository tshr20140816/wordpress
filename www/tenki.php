<?php

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi}");

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
  error_log("${pid} ACCESS TOKEN NONE");
  $pdo = null;
  exit();
}

if ($refresh_flag == 1) {
  error_log("${pid} refresh_token : ${refresh_token}");
  $post_data = ['grant_type' => 'refresh_token', 'refresh_token' => $refresh_token];
  
  $res = get_contents(
    'https://api.toodledo.com/3/account/token.php',
    [CURLOPT_USERPWD => getenv('TOODLEDO_CLIENTID') . ':' . getenv('TOODLEDO_SECRET'),
     CURLOPT_POST => TRUE,
     CURLOPT_POSTFIELDS => http_build_query($post_data),
    ]);
  
  error_log("${pid} token.php RESPONSE : ${res}");
  $params = json_decode($res, TRUE);
  
  $sql = <<< __HEREDOC__
UPDATE m_authorization
   SET access_token = :b_access_token
      ,refresh_token = :b_refresh_token
      ,update_time = LOCALTIMESTAMP;
__HEREDOC__;
  
  $statement = $pdo->prepare($sql);
  $rc = $statement->execute([':b_access_token' => $params['access_token'],
                             ':b_refresh_token' => $params['refresh_token']]);
  error_log("${pid} UPDATE RESULT : ${rc}");
  
  $access_token = $params['access_token'];
}

$pdo = null;

// Get Contexts

$res = get_contents('https://api.toodledo.com/3/contexts/get.php?access_token=' . $access_token, NULL);
$contexts = json_decode($res, TRUE);

$context_id_list = [];
for ($i = 0; $i < count($contexts); $i++) {
  switch ($contexts[$i]['name']) {
    case '日......':
      $context_id_list[0] = $contexts[$i]['id'];
      break;
    case '.月.....':
      $context_id_list[1] = $contexts[$i]['id'];
      break;
    case '..火....':
      $context_id_list[2] = $contexts[$i]['id'];
      break;
    case '...水...':
      $context_id_list[3] = $contexts[$i]['id'];
      break;
    case '....木..':
      $context_id_list[4] = $contexts[$i]['id'];
      break;
    case '.....金.':
      $context_id_list[5] = $contexts[$i]['id'];
      break;
    case '......土':
      $context_id_list[6] = $contexts[$i]['id'];
      break;
  }
}

error_log($pid . ' ' . print_r($context_id_list, TRUE));

// Moon

$yyyy = date('Y');
$mm = date('m');

$res = get_contents('https://eco.mtk.nao.ac.jp/koyomi/dni/' . $yyyy . '/m' . getenv('AREA_ID') . $mm . '.html', NULL);

$tmp = explode('<table ', $res);
$tmp = explode('</table>', $tmp[1]);
$tmp = explode('</tr>', $tmp[0]);
array_shift($tmp);
array_pop($tmp);

$dt = date('Y-m-') . '01';

$list_moon_age = [];
for ($i = 0; $i < count($tmp); $i++) {
  $timestamp = strtotime("${dt} +${i} day");
  $rc = preg_match('/.+<td>(.+?)</', $tmp[$i], $matches);
  // error_log(trim($matches[1]));
  $list_moon_age[$timestamp] = trim($matches[1]);
}
error_log($pid . ' $list_moon_age : ' . print_r($list_moon_age, TRUE));

// Weather Information

$res = get_contents('https://tenki.jp/week/' . getenv('LOCATION_NUMBER') . '/', NULL);

$rc = preg_match('/announce_datetime:(\d+-\d+-\d+) (\d+)/', $res, $matches);

error_log($pid . ' $matches[0] : ' . $matches[0]);
error_log($pid . ' $matches[1] : ' . $matches[1]);
error_log($pid . ' $matches[2] : ' . $matches[2]);

$dt = $matches[1]; // yyyy-mm-dd
$update_marker = ' __' . substr($matches[1], 8) . $matches[2] . '__'; // __DDHH__

// To Small Size
$subscript = '₀₁₂₃₄₅₆₇₈₉';
for ($i = 0; $i < 10; $i++) {
  $update_marker = str_replace($i, mb_substr($subscript, $i, 1), $update_marker);
}

$tmp = explode(getenv('POINT_NAME'), $res);
$tmp = explode('<td class="forecast-wrap">', $tmp[1]);
$list_yobi = array('日', '月', '火', '水', '木', '金', '土');
$list_weather = [];
for ($i = 0; $i < 10; $i++) {
  // ex) ##### 日曜日 01/13 ##### ☂/☀ 60% 25/18 __₁₀₁₀__
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
    . $list_yobi[date('w', $timestamp)] . '曜日 '
    . date('m/d', $timestamp)
    . ' ##### '
    . $tmp2 . ' ' . $list[2] . ' ' . $list[1]
    . $update_marker;

  if (array_key_exists($timestamp, $list_moon_age)) {
    $tmp3 .= ' 🌙' . $list_moon_age[$timestamp];
  }
  
  error_log("${pid} ${tmp3}");

  $list_weather[] = '{"title":"' . $tmp3
    . '","duedate":"' . $timestamp
    . '","context":"' . $context_id_list[date('w', $timestamp)]
    . '","tag":"WEATHER","folder":"__FOLDER_ID__"}';
}

if (count($list_weather) == 0) {
  error_log("${pid} WEATHER DATA NONE");
  exit();
}

// Get Tasks

$res = get_contents('https://api.toodledo.com/3/tasks/get.php?access_token=' . $access_token . '&comp=0&fields=tag', NULL);
// error_log($res);

$tasks = json_decode($res, TRUE);
// error_log(print_r($tasks, TRUE));
$list_delete_task = [];
for ($i = 0; $i < count($tasks); $i++) {
  if (array_key_exists('id', $tasks[$i]) && array_key_exists('tag', $tasks[$i])) {
    if ($tasks[$i]['tag'] == 'WEATHER') {
      $list_delete_task[] = $tasks[$i]['id'];
      error_log("${pid} DELETE TARGET TASK ID : " . $tasks[$i]['id']);
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
    error_log("${pid} WEATHER FOLDER ID : ${weather_folder_id}");
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

error_log("${pid} add.php RESPONSE : ${res}");

// Delete Tasks

error_log("${pid} DELETE TARGET TASK COUNT : " . count($list_delete_task));

if (count($list_delete_task) > 0) {
  $post_data = ['access_token' => $access_token, 'tasks' => '[' . implode(',', $list_delete_task) . ']'];  
  $res = get_contents(
    'https://api.toodledo.com/3/tasks/delete.php',
    [CURLOPT_POST => TRUE,
     CURLOPT_POSTFIELDS => http_build_query($post_data),
    ]);
  error_log("${pid} delete.php RESPONSE : ${res}");
}

error_log("${pid} FINISH");

$res = get_contents(
  'https://' . getenv('HEROKU_APP_NAME') . '.herokuapp.com/weekday.php',
  [CURLOPT_USERPWD => getenv('BASIC_USER') . ':' . getenv('BASIC_PASSWORD'),
  ]);

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
