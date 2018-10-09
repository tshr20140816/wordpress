<?php

error_log('START');

error_log(getenv('WEATHER_URL') . 'november-weather/' . getenv('WEATHER_SUB_CODE'));

//$res = get_contents(getenv('WEATHER_URL') . 'november-weather/' . getenv('WEATHER_SUB_CODE'), NULL);
$res = get_contents('https://www.accuweather.com/ja/jp/japan-weather', NULL);

error_log($res);

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
    ]);
  if (is_null($options_) == FALSE) {
    curl_setopt_array($ch, $options_);
  }
  $res = curl_exec($ch);
  curl_close($ch);
  
  return $res;
}
?>
