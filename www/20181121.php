<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$timestamp = strtotime('+1 day');
$yyyy = date('Y', $timestamp);
$mm = date('m', $timestamp);

$res = $mu->get_contents('https://eco.mtk.nao.ac.jp/koyomi/dni/' . $yyyy . '/s' . getenv('AREA_ID') . $mm . '.html');

$res = mb_convert_encoding($res, 'UTF-8', 'EUC-JP');

$tmp = explode('<table ', $res);
$tmp = explode('</table>', $tmp[1]);
$tmp = explode('</tr>', $tmp[0]);
array_shift($tmp);
array_pop($tmp);

//error_log(print_r($tmp, TRUE));

for ($i = 0; $i < count($tmp); $i++) {
  $rc = preg_match('/<tr><td.*?>' . substr(' ' . date('j', $timestamp), -2) . '</td>/', $tmp[$i]);
  if ($rc == 1) {
    error_log($tmp[$i]);
    break;
  }
}
?>
