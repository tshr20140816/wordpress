<?php

$url = 'https://soccer.yahoo.co.jp/jleague/teams/schedule/129';

$res = file_get_contents($url);

error_log($res);

$rc = preg_match_all('/<td class="textC">(.+?)<\/tr>/s', $res, $matches);

error_log(print_r($matches, TRUE));

?>
