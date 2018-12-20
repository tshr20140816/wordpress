<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$list_shisu = get_shisu($mu);

error_log(print_r($list_shisu, TRUE));

$timestamp = 1545264000;

if (array_key_exists($timestamp, $list_shisu[getenv('URL_KASA_SHISU')])) {
  error_log('HIT');
} else {
  error_log('NONE');
}

$timestamp = strtotime("2018-12-20 +0 day") + 9 * 60 * 60;
error_log($timestamp);

function get_shisu($mu_) {
  $timestamp = strtotime(date('j F Y', strtotime('+9 hours')));
  $list_shisu = [];
  foreach([getenv('URL_TAIKAN_SHISU'), getenv('URL_KASA_SHISU')] as $url) {
    $res = $mu_->get_contents($url);
    $rc = preg_match('/<!-- today index -->.+?<span class="indexes-telop-0">(.+?)<\/span>/s', $res, $matches);
    $list_shisu[$url][$timestamp] = $matches[1];
    $rc = preg_match('/<!-- tomorrow index -->.+?<span class="indexes-telop-0">(.+?)<\/span>/s', $res, $matches);
    $list_shisu[$url][$timestamp + 24 * 60 * 60] = $matches[1];
    $rc = preg_match('/<!-- week -->(.+?)<!-- \/week -->/s', $res, $matches);
    $rc = preg_match_all('/<p class="indexes-telop-0">(.+?)<\/p>/s', $matches[1], $matches2, PREG_SET_ORDER);
    for($i = 0; $i < count($matches2); $i++) {
      $list_shisu[$url][$timestamp + 24 * 60 * 60 * ($i + 2)] = $matches2[$i][1];
    }
    error_log(getmypid() . ' [' . __METHOD__ . '] $list_shisu : ' . print_r($list_shisu, TRUE));
  }
  return $list_shisu;
}
?>
