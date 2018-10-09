<?php

$res = file_get_contents(getenv('WEATHER_URL') . '/november-weather/' . getenv('WEATHER_SUB_CODE'));

error_log($res);

?>
