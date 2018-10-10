<?php

error_log('START');

error_log('https://feed43.com/' . getenv('SUB_ADDRESS') . '06-10.xml');

$res = get_contents('https://feed43.com/' . getenv('SUB_ADDRESS') . '06-10.xml', NULL);

error_log($res);

$tmp = explode("\n", $res);
error_log(print_r($tmp, TRUE));

function get_contents($url_, $options_) {
  $ch = curl_init();
  curl_setopt_array($ch, [
    CURLOPT_URL => $url_,
    CURLOPT_RETURNTRANSFER => TRUE,
    CURLOPT_ENCODING => '',
    CURLOPT_FOLLOWLOCATION => 1,
    CURLOPT_MAXREDIRS => 3,
    CURLOPT_SSL_FALSESTART => TRUE,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; rv:56.0) Gecko/20100101 Firefox/62.0',
    CURLOPT_HTTPHEADER => [
      'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
      'Accept-Language:ja,en-us;q=0.7,en;q=0.3',
      ],
    ]);
  if (is_null($options_) == FALSE) {
    curl_setopt_array($ch, $options_);
  }
  $res = curl_exec($ch);
  curl_close($ch);
  
  return $res;
}
?>
