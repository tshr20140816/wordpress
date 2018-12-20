<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$url = 'https://www.googleapis.com/calendar/v3/users/me/calendarList?key=' . getenv('GOOGLE_API_KEY');

$mu = new MyUtils();
$options = [
  CURLOPT_REFERER => 'https://' . getenv('HEROKU_APP_NAME') . '.herokuapp.com/',
  ];
$res = $mu->get_contents($url, $options);

error_log($res);
?>
