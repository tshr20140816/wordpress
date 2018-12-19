<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$url = 'https://tenki.jp/indexes/self_temp/6/30/6200/';

$res = $mu->get_contents($url);


?>
