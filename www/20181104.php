<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$y = date('Y');
$m = date('n');

$list_library = [];
for ($j = 0; $j < 2; $j++) {
  $url = 'http://www.cf.city.hiroshima.jp/saeki-cs/sche6_park/sche6.cgi?year=' . $y . '&mon=' . $m;

  $res = $mu->get_contents($url, NULL);
  $res = mb_convert_encoding($res, 'UTF-8', 'SJIS');

  // error_log($res);

  $tmp = explode('<col span=1 align=right>', $res);
  $tmp = explode('</table>', $tmp[1]);

  // error_log($tmp[0]);

  $rc = preg_match_all('/<tr .+?<b>(.+?)<.*?<td(.*?)<\/td><\/tr>/s', $tmp[0], $matches, PREG_SET_ORDER);

  error_log(print_r($matches, TRUE));

  for ($i = 0; $i < count($matches); $i++) {
    $timestamp = mktime(0, 0, 0, $m, $matches[$i][1], $y);
    if (date('Ymd') > date('Ymd', $timestamp)) {
      continue;
    }
    $tmp = $matches[$i][2];
    $tmp = trim($tmp, " \t\n\r\0\t>");
    $tmp = preg_replace('/<font .+?>.+?>/', '', $tmp);
    $tmp = str_replace('　', '', $tmp);
    $tmp = preg_replace('/bgcolor.+?>/', '', $tmp);
    $tmp = trim(str_replace('<br>', ' ', $tmp));
    if (strlen($tmp) > 0) {
      $list_library[$timestamp] = date('m/d', $timestamp) . ' 文セ ★ ' . $tmp;
      // error_log(date('m/d', $timestamp) . ' 文セ ★ ' . $tmp);
    }
  }
  if ($m == 12) {
    $yyyy++;
    $m = 1;
  } else {
    $m++;
  }
}
error_log(print_r($list_library, TRUE));

?>
