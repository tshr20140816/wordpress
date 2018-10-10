<?php

error_log('START');

error_log('https://feed43.com/' . getenv('SUB_ADDRESS') . '06-10.xml');

$res = get_contents('https://feed43.com/' . getenv('SUB_ADDRESS') . '06-10.xml', NULL);

error_log($res);

$tmp = explode("\n", $res);
error_log(print_r($tmp, TRUE));

foreach (explode("\n", $res) as $one_line) {
  if (strpos($one_line, '<title>') !== FALSE) {
    error_log($one_line);
  }
}

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
