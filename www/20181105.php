<?php

$url = 'https://soccer.yahoo.co.jp/jleague/teams/schedule/129';

$res = file_get_contents($url);

// error_log($res);

$rc = preg_match_all('/<td class="textC">(.+?)<\/tr>/s', $res, $matches, PREG_SET_ORDER);

// error_log(print_r($matches, TRUE));

for ($i < 0; $i < count($matches); $i++) {
  // error_log(print_r($matches[$i], TRUE));
  $tmp = $matches[$i][1];
  $rc = preg_match('/(.+?)<\/td>.+?<span.*?>(.+?)<\/span>.*?<td class="team">(.+?)<\/td>.+?<td class="team">(.+?)<\/td>.*?<td class="">(.+?)<\/td>/s', $tmp, $matches2);
  
  if ($rc == 1) {
    // error_log(print_r($matches2, TRUE));
    error_log($matches2[1]);
    error_log($matches2[2]);
    error_log(trim(preg_replace('/<.+?>/s', '', $matches2[3])));
    error_log(trim(preg_replace('/<.+?>/s', '', $matches2[4])));
    error_log($matches2[5]);
  }
}
?>
