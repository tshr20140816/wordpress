<?php

$res = file_get_contents('https://tenki.jp/week/' . getenv('LOCATION_NUMBER') . '/');

$tmp = explode(getenv('POINT_NAME'), $res);

error_log($tmp[1]);

?>
