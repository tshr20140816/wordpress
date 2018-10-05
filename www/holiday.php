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
      ,refresh_token = :b_refresh_token
      ,update_time = LOCALTIMESTAMP;
__HEREDOC__;

  $statement = $pdo->prepare($sql);
  $rc = $statement->execute([':b_access_token' => $params['access_token'],
                             ':b_refresh_token' => $params['refresh_token']]);
  error_log('UPDATE RESULT : ' . $rc);

  $access_token = $params['access_token'];
}

$pdo = null;

// Get Tasks

$res = file_get_contents('https://api.toodledo.com/3/tasks/get.php?access_token=' . $access_token . '&comp=0&fields=tag');
// error_log($res);

$tasks = json_decode($res, TRUE);
// error_log(print_r($tasks, TRUE));
$list_holiday_task_title = [];
for ($i = 0; $i < count($tasks); $i++) {
  if (array_key_exists('id', $tasks[$i]) && array_key_exists('tag', $tasks[$i])) {
    if ($tasks[$i]['tag'] == 'HOLIDAY') {
      $list_holiday_task_title[] = $tasks[$i]['title'];
      error_log($tasks[$i]['title']);
    }
  }
}

// Holiday

// $url = 'http://calendar-service.net/cal?start_year=2018&start_mon=11&end_year=2020&end_mon=12&year_style=normal&month_style=numeric&wday_style=ja_full&format=csv&holiday_only=1&zero_padding=1';
$url = 'http://calendar-service.net/cal?start_year=2018&start_mon=11&end_year=2018&end_mon=12&year_style=normal&month_style=numeric&wday_style=ja_full&format=csv&holiday_only=1&zero_padding=1';

$res = file_get_contents($url);

$res = mb_convert_encoding($res, 'UTF-8', 'EUC-JP');

// error_log($res);

$tmp_list = explode("\n", $res);
$holiday_list = [];
for ($i = 1; $i < count($tmp_list) - 1; $i++) {
  error_log($tmp_list[$i]);
  $tmp = explode(',', $tmp_list[$i]);
  error_log('####+ ' . $tmp[7] . ' ' . $tmp[0] . '/' . $tmp[1] . '/' . $tmp[2] . ' +####');
  $holiday_list[] = '####+ ' . $tmp[7] . ' ' . $tmp[0] . '/' . $tmp[1] . '/' . $tmp[2] . ' +####';
}

$holiday_diff_list = array_diff($holiday_list, $list_holiday_task_title);

error_log(print_r($holiday_diff_list, TRUE));

?>
