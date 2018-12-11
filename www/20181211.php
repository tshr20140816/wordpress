<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$res = $mu->get_contents_nocache('https://www.yahoo.co.jp/');

?>
