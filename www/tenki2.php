<?php

error_log('START');

error_log(getenv('WEATHER_URL') . 'november-weather/' . getenv('WEATHER_SUB_CODE'));

$res = file_get_contents(getenv('WEATHER_URL') . 'november-weather/' . getenv('WEATHER_SUB_CODE'));
// $res = file_get_contents('https://www.accuweather.com/ja/jp/hiroshima-shi/223955/october-weather/223955?monyr=10/1/2018&view=table');

error_log($res);

?>
