<?php

class MyUtils
{
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
}
?>