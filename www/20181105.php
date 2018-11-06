<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi}");

$mu = new MyUtils();

$access_token = $mu->get_access_token();

$label_folder_id = $mu->get_folder_id('LABEL');

$url = 'https://fukkou-shuyu.jp/';

$res = $mu->get_contents($url, [CURLOPT_HEADER => 1]);

$rc = preg_match('/Last-Modified: (.+)/', $res, $matches);

error_log(print_r($matches, TRUE));

$tmp = strtotime($matches[1]);

error_log(date('Y/m/d H:i:s', $tmp));
error_log(date('Y/m/d H:i:s', strtotime(date('Y/m/d H:i:s') . ' + 9 hours'));

error_log("${pid} FINISH");

exit();

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

?>
