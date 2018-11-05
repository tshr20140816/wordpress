<?php

class MyUtils
{
  private $_pid;
  private $_pdo;
  
  function __construct() {
    $_pid = getmypid();
    
    $connection_info = parse_url(getenv('DATABASE_URL'));
    $pdo = new PDO(
      "pgsql:host=${connection_info['host']};dbname=" . substr($connection_info['path'], 1),
      $connection_info['user'],
      $connection_info['pass']);
  }
  
  function __destruct() {
    $_pdo = NULL;
  }
  
  function get_access_token() {
    
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
    foreach ($_pdo->query($sql) as $row) {
      $access_token = $row['access_token'];
      $refresh_token = $row['refresh_token'];
      $refresh_flag = $row['refresh_flag'];
    }
    
    if ($access_token == NULL) {
      error_log("${_pid} ACCESS TOKEN NONE");
      exit();
    }
    
    return $access_token;
  }
  
  function get_contents($url_, $options_ = NULL) {
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
}
?>
