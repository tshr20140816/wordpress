<?php

// $url = 'http://calendar-service.net/cal?start_year=2018&start_mon=11&end_year=2020&end_mon=12&year_style=normal&month_style=numeric&wday_style=ja_full&format=csv&holiday_only=1&zero_padding=1';
$url = 'http://calendar-service.net/cal?start_year=2018&start_mon=11&end_year=2018&end_mon=11&year_style=normal&month_style=numeric&wday_style=ja_full&format=csv&holiday_only=1&zero_padding=1';

$res = file_get_contents($url);

$res = mb_convert_encoding($res, 'UTF-8', 'EUC-JP');

// error_log($res);

$tmp = explode("\n", $res);
error_log($tmp[1]);
error_log($tmp[2]);

?>
