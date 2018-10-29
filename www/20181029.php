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

// Get Folders

$res = get_contents('https://api.toodledo.com/3/folders/get.php?access_token=' . $access_token, NULL);
$folders = json_decode($res, TRUE);

$label_folder_id = 0;
for ($i = 0; $i < count($folders); $i++) {
  if ($folders[$i]['name'] == 'LABEL') {
    $label_folder_id = $folders[$i]['id'];
    error_log($pid . ' LABEL FOLDER ID : ' . $label_folder_id);
    break;
  }
}

// Get Tasks

$res = get_contents('https://api.toodledo.com/3/tasks/get.php?access_token=' . $access_token . '&comp=0&fields=folder,duedate', NULL);
// error_log($res);

$tasks = json_decode($res, TRUE);
// error_log(print_r($tasks, TRUE));

$list_label_task = [];
$list_schedule_task = [];
for ($i = 0; $i < count($tasks); $i++) {
  if (array_key_exists('duedate', $tasks[$i]) && array_key_exists('folder', $tasks[$i])) {
    if ($tasks[$i]['folder'] == $label_folder_id) {
      $list_label_task[] = $tasks[$i]['duedate'];
    } else {
      $list_schedule_task[] = $tasks[$i]['duedate'];
    }
  }
}

$list_non_label = array_values(array_diff($list_schedule_task, $list_label_task));
sort($list_non_label);
error_log(print_r($list_non_label, TRUE));

$list_additional_label = [];
$list_yobi = array('日', '月', '火', '水', '木', '金', '土');
$subscript = '₀₁₂₃₄₅₆₇₈₉';
$timestamp = strtotime('+20 day');
for ($i = 0; $i < count($list_non_label); $i++) {
  if ($list_non_label[$i] > $timestamp) {
    // error_log(date('Y-m-d', $list_non_label[$i]));
    
    $yyyy = date('Y', $list_non_label[$i]);
    // To Small Size
    for ($j = 0; $j < 10; $j++) {
      $yyyy = str_replace($j, mb_substr($subscript, $j, 1), $yyyy);
    }
    
    $tmp = '##### '
      . $list_yobi[date('w', $list_non_label[$i])] . '曜日 '
      . date('m/d', $list_non_label[$i])
      . ' ##### '
      . $yyyy;
    // error_log($tmp);
    $list_additional_label[] = '{"title":"' . $tmp
      . '","duedate":"' . $list_non_label[$i]
      . '","tag":"ADDITIONAL","folder":"' . $label_folder_id . '"}';
  }
}
error_log(print_r($list_additional_label, TRUE));


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
