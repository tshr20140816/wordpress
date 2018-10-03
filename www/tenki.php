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
  $rc = $statement->execute(
    [':b_access_token' => $params['access_token']]);
  error_log('UPDATE RESULT : ' . $rc);
  
  $access_token = $params['access_token'];
}

$pdo = null;
?>
