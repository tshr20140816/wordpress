<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$yyyy = '2018';
$mm = '12';

$res = $mu->get_contents('https://eco.mtk.nao.ac.jp/koyomi/dni/' . $yyyy . '/s' . getenv('AREA_ID') . $mm . '.html');

$tmp = explode('<table ', $res);
$tmp = explode('</table>', $tmp[1]);
$tmp = explode('</tr>', $tmp[0]);
array_shift($tmp);
array_pop($tmp);

error_log(print_r($tmp, TRUE));

?>
