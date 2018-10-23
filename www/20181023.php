<?php

$res = file_get_contents('https://eco.mtk.nao.ac.jp/koyomi/dni/2018/m2011.html');

$tmp = explode('</th>', $res);
$tmp = explode('</table>', end($tmp));
$tmp = explode('</tr>', $tmp[0]);

error_log(print_r($tmp, TRUE));

?>
