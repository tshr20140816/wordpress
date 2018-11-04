<?php

include(dirname(__FILE__) . "/../classes/MyUtils.php");

$mu = new MyUtils();

$y = date('Y');
$m = date('n');

for ($j = 0; $j < 2; $j++) {
  $url = 'http://www.cf.city.hiroshima.jp/saeki-cs/sche6_park/sche6.cgi?year=' . $y . '&mon=' . $m;

  // $res = file_get_contents($url);
  $res = $mu->get_contents($url, NULL);
  $res = mb_convert_encoding($res, 'UTF-8', 'SJIS');

  // error_log($res);

  $tmp = explode('<col span=1 align=right>', $res);
  $tmp = explode('</table>', $tmp[1]);

  // error_log($tmp[0]);

  $rc = preg_match_all('/<tr .+?<b>(.+?)<.*?<td(.*?)<\/td><\/tr>/s', $tmp[0], $matches, PREG_SET_ORDER);

  error_log(print_r($matches, TRUE));

  for ($i = 0; $i < count($matches); $i++) {
    $tmp = $matches[$i][2];
    $tmp = trim($tmp, " \t\n\r\0\t>");
    $tmp = preg_replace('/<font .+?>.+?>/', '', $tmp);
    if (strlen($tmp) > 0) {
      $tmp = str_replace('　', '', $tmp);
      $tmp = preg_replace('/bgcolor.+?>/', '', $tmp);
      $tmp = trim(str_replace('<br>', ' ', $tmp));
      $timestamp = mktime(0, 0, 0, $m, $matches[$i][1], $y);
      error_log(date('m/d', $timestamp) . ' ★ ' . $tmp);
    }
  }
  if ($m == 12) {
    $yyyy++;
    $m = 1;
  } else {
    $m++;
  }
}

?>
