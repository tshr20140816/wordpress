<?php

error_log('START');

//error_log(date('m/d', strtotime('+10 days')));
error_log(date('m/j', strtotime('+10 days')));

$list_base = [];
for ($i = 0; $i < 3; $i++) {
  $url = 'https://feed43.com/' . getenv('SUB_ADDRESS') . ($i * 5 + 11) . '-' . ($i * 5 + 15) . '.xml';
  error_log($url);
  $res = get_contents($url, NULL);
  error_log($res);
  foreach (explode("\n", $res) as $one_line) {
    if (strpos($one_line, '<title>_') !== FALSE) {
      // error_log($one_line);
      $tmp = explode('_', $one_line);
      $list_base[$tmp[1]] = $tmp[2];
    }
  }
}
error_log(print_r($list_base, TRUE));

for ($i = 0; $i < 15; $i++) {
  $dt = date('m/j', strtotime('+' . ($i + 10) . ' days'));
  
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
