<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$url = getenv('URL_KASA_SHISU');
$res = $mu->get_contents($url);

$rc = preg_match('/<!--指数情報-->.+?<span>傘指数(.+?)<.+?<p class="index_text">(.+?)</s', $res, $matches);

array_shift($matches);
error_log(print_r($matches, TRUE));

?>
