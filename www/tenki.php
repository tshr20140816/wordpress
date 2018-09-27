<?php

$res = file_get_contents('https://rss-weather.yahoo.co.jp/rss/days/' . getenv('LOCATION_NUMBER') . '.xml');

echo $res;

?>
