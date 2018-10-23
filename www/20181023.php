<?php

$res = file_get_contents('https://eco.mtk.nao.ac.jp/koyomi/dni/2018/m2011.html');

$tmp = exlode('</tr>', $res);

error_log(print_r($tmp, TRUE));

?>
