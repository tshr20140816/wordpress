<?php

$yyyy = date('Y');
$mm = date('m');

$res = file_get_contents('https://eco.mtk.nao.ac.jp/koyomi/dni/' . $yyyy . '/m' . getenv('AREA_ID') . $mm . '.html');

$tmp = explode('<table ', $res);
$tmp = explode('</table>', $tmp[1]);
$tmp = explode('</tr>', $tmp[0]);
array_shift($tmp);
array_pop($tmp);

error_log(print_r($tmp, TRUE));

for ($i = 0; $i < count($tmp); $i++) {
  // error_log($tmp[$i]);
  $rc = preg_match('/.+<td>(.+?)</', $tmp[$i], $matches);
  error_log(trim($matches[1]));
}

?>
