<?php

class MyUtils
{
  private $_pdo;
  
  function __construct() {
    $connection_info = parse_url(getenv('DATABASE_URL'));
    $pdo = new PDO(
      "pgsql:host=${connection_info['host']};dbname=" . substr($connection_info['path'], 1),
      $connection_info['user'],
      $connection_info['pass']);
  }
  
  function __destruct() {
    $_pdo = NULL;
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
