<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$res = $mu->get_contents(getenv('URL_TAIKAN_SHISU'));

// error_log($res);

$rc = preg_match('/<!-- today index -->.+?<span class="indexes-telop-0">(.+?)<\/span>/s', $res, $matches);

error_log(print_r($matches, TRUE));

$res = $mu->get_contents(getenv('URL_KASA_SHISU'));

$rc = preg_match('/<!-- today index -->.+?<span class="indexes-telop-0">(.+?)<\/span>/s', $res, $matches);

error_log(print_r($matches, TRUE));

$rc = preg_match('/<!-- tomorrow index -->.+?<span class="indexes-telop-0">(.+?)<\/span>/s', $res, $matches);

error_log(print_r($matches, TRUE));

$rc = preg_match('/<!-- week -->(.+?)<!-- \/week -->/s', $res, $matches);
$rc = preg_match_all('/<p class="indexes-telop-0">(.+?)<\/p>/s', $matches[1], $matches2, PREG_SET_ORDER);

error_log(print_r($matches2, TRUE));

$list = get_shisu($mu);

error_log(print_r($list, TRUE));

function get_shisu($mu_) {
  
  $timestamp = strtotime('+9 hours');
  
  // $res = $mu_->get_contents(getenv('URL_TAIKAN_SHISU'));
  // $res = $mu_->get_contents(getenv('URL_KASA_SHISU'));
  
  foreach([getenv('URL_TAIKAN_SHISU'), getenv('URL_KASA_SHISU')] as $url) {
  
    $rc = preg_match('/<!-- today index -->.+?<span class="indexes-telop-0">(.+?)<\/span>/s', $res, $matches);
    $list[$url][$timestamp] = $matches[1];
  
    $rc = preg_match('/<!-- tomorrow index -->.+?<span class="indexes-telop-0">(.+?)<\/span>/s', $res, $matches);
    $list[$url][$timestamp + 24 * 60 * 60] = $matches[1];
  
    $rc = preg_match('/<!-- week -->(.+?)<!-- \/week -->/s', $res, $matches);
    $rc = preg_match_all('/<p class="indexes-telop-0">(.+?)<\/p>/s', $matches[1], $matches2, PREG_SET_ORDER);
  
    for($i = 0; $i < count($matches2); $i++) {
      $list[$url][$timestamp + 24 * 60 * 60 * ($i + 2)] = $matches2[$i][1];
    }
  }
  return $list;
}
?>
