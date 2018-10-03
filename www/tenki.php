<?php

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
  $post_data = ['grant_type' => 'refresh_token', 'refresh_token ' => $refresh_token];
  
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
      ,update_time = LOCALTIMESTAMP;
__HEREDOC__;
  
  $statement = $pdo->prepare($sql);
  $rc = $statement->execute([':b_access_token' => $params['access_token']]);
  error_log('UPDATE RESULT : ' . $rc);
  
  $access_token = $params['access_token'];
}

$pdo = null;

$res = file_get_contents('https://tenki.jp/week/' . getenv('LOCATION_NUMBER') . '/');

$rc = preg_match('/announce_datetime:(\d+-\d+-\d+)/', $res, $matches);

error_log($matches[0]);
error_log($matches[1]);

$dt = $matches[1];
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
  error_log('##### ' . $list_yobi[date('w', strtotime($dt . ' +' . $i . ' day'))] . '曜日 ' . date('m/d', strtotime($dt . ' +' . $i . ' day')) . ' ##### ' . $tmp2 . ' ' . $list[2] . ' ' . $list[1]);
  $list_weather[] = '{"title":"' . '##### ' . $list_yobi[date('w', strtotime($dt . ' +' . $i . ' day'))] . '曜日 ' . date('m/d', strtotime($dt . ' +' . $i . ' day')) . ' ##### ' . $tmp2 . ' ' . $list[2] . ' ' . $list[1] . '","duedate":"' . strtotime($dt . ' +' . $i . ' day') . '","tag":"WEATHER"}';
}

if (count($list_weather) == 0) {
  exit();
}

?>
