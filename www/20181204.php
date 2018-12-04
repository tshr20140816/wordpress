<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$res = $mu->get_contents2('http://www.jma.go.jp/jp/amedas_h/today-' . getenv('AMEDAS') . '.html');


?>
