<?php

$res = file_get_contents('https://tenki.jp/week/' . getenv('LOCATION_NUMBER') . '/');

$tmp = explode(getenv('POINT_NAME'), $res);
$tmp = explode('<td class="forecast-wrap">', $tmp[1]);

for ($i = 1; $i < 11; $i++) {
  error_log(str_replace(' ', '', trim(strip_tags($tmp[$i]))));
}

?>
