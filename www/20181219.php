<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$res = $mu->get_contents(getenv('URL_TAIKAN_SHISU'));

// error_log($res);

$rc = preg_match('/<!-- today index -->.+?<span class="indexes-telop-0">(.+?)<\/span>/s', $res, $matches);

error_log(print_r($matches, TRUE));

$res = $mu->get_contents(getenv('URL_KASA_SHISU2'));

$rc = preg_match('/<!-- today index -->.+?<span class="indexes-telop-0">(.+?)<\/span>/s', $res, $matches);

error_log(print_r($matches, TRUE));

$rc = preg_match('/<!-- tomorrow index -->.+?<span class="indexes-telop-0">(.+?)<\/span>/s', $res, $matches);

error_log(print_r($matches, TRUE));

$rc = preg_match('/<!-- week -->(.+?)<!-- \/week -->/s', $res, $matches);
$rc = preg_match_all('/<p class="indexes-telop-0">(.+?)<\/p>/s', $matches[1], $matches2, PREG_SET_ORDER);

error_log(print_r($matches2, TRUE));

?>
