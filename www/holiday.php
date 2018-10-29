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

// Get Folders

$res = get_contents('https://api.toodledo.com/3/folders/get.php?access_token=' . $access_token, NULL);
$folders = json_decode($res, TRUE);

$label_folder_id = 0;
for ($i = 0; $i < count($folders); $i++) {
  if ($folders[$i]['name'] == 'LABEL') {
    $label_folder_id = $folders[$i]['id'];
    error_log("${pid} LABEL FOLDER ID : ${label_folder_id}");
    break;
  }
}

// Get Tasks

$res = get_contents('https://api.toodledo.com/3/tasks/get.php?access_token=' . $access_token . '&comp=0&fields=tag,folder,duedate', NULL);
// error_log($res);

$tasks = json_decode($res, TRUE);
// error_log(print_r($tasks, TRUE));

$list_label_task = [];
for ($i = 0; $i < count($tasks); $i++) {
  if (array_key_exists('duedate', $tasks[$i]) && array_key_exists('folder', $tasks[$i])) {
    if ($tasks[$i]['folder'] == $label_folder_id) {
      $list_label_task[] = $tasks[$i]['duedate'];
      error_log($pid . ' ' . date('Y-m-d', $tasks[$i]['duedate']) . ' ' . $tasks[$i]['duedate']);
    }
  }
}
error_log($pid . ' $list_label_task : ' . print_r($list_label_task, TRUE));

// Holiday

$start_yyyy = date('Y', strtotime('+2 month'));
$start_m = date('n', strtotime('+2 month'));
$finish_yyyy = $start_yyyy + 2;
// $finish_m = 12;

$url = 'http://calendar-service.net/cal?start_year=' . $start_yyyy . '&start_mon=' . $start_m . '&end_year=' . $finish_yyyy . '&end_mon=12&year_style=normal&month_style=numeric&wday_style=ja_full&format=csv&holiday_only=1&zero_padding=1';

$res = get_contents($url, NULL);

$res = mb_convert_encoding($res, 'UTF-8', 'EUC-JP');

error_log($pid . ' $res : ' . $res);

$subscript = '₀₁₂₃₄₅₆₇₈₉';
$list_tmp = explode("\n", $res);
$list_holiday = [];
$add_task_template = '{"title":"__TITLE__","duedate":"__DUEDATE__","tag":"HOLIDAY","folder":"' . $label_folder_id . '"}';
for ($i = 1; $i < count($list_tmp) - 1; $i++) {
  error_log($pid . ' $list_tmp[$i] : ' . $list_tmp[$i]);
  $tmp = explode(',', $list_tmp[$i]);
  $yyyy = $tmp[0];
  // To Small Size
  for ($j = 0; $j < 10; $j++) {
    $yyyy = str_replace($j, mb_substr($subscript, $j, 1), $yyyy);
  }
  $tmp1 = '##### ' . $tmp[5] . ' ' . $tmp[1] . '/' . $tmp[2] . ' ' . $tmp[7] . ' ##### ' . $yyyy;
  error_log($pid . ' $tmp1 : ' . $tmp1);
  $timestamp = mktime(0, 0, 0, $tmp[1], $tmp[2], $tmp[0]);
  if (!in_array($timestamp, $list_label_task)) {
    error_log($pid . ' TARGET TIMESTAMP : ' . $timestamp);
    $tmp1 = str_replace('__TITLE__', $tmp1, $add_task_template);
    $tmp1 = str_replace('__DUEDATE__', $timestamp, $tmp1);    
    $list_holiday[] = $tmp1;
  }
}
$list_holiday = array_slice($list_holiday, 0, 50);
error_log($pid . ' $list_holiday : ' . print_r($list_holiday, TRUE));

if (count($list_holiday) == 0) {
  exit();
}

$tmp = implode(',', $list_holiday);
$post_data = ['access_token' => $access_token, 'tasks' => "[${tmp}]"];

error_log($pid . ' ' . print_r($post_data, TRUE));

$res = get_contents(
  'https://api.toodledo.com/3/tasks/add.php',
  [CURLOPT_POST => TRUE,
   CURLOPT_POSTFIELDS => http_build_query($post_data),
  ]);
error_log("${pid} add.php RESPONSE : ${res}");

error_log("${pid} FINISH");

exit();

function get_contents($url_, $options_) {
  $ch = curl_init();
  curl_setopt_array($ch, [
    CURLOPT_URL => $url_,
    CURLOPT_USERAGENT => getenv('USER_AGENT'),
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
