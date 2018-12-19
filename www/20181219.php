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

?>
