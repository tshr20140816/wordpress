<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$res = $mu->get_contents('https://eco.mtk.nao.ac.jp/koyomi/dni/2018/s3512.html', NULL, TRUE);

// error_log($res);

?>
