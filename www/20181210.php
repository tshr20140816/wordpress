<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$post_data = ['from_year' => 2020];
error_log(http_build_query($post_data));

exit();

$res = $mu->get_contents(
  'http://www.calc-site.com/calendars/solar_year',
  [CURLOPT_POST => TRUE,
   CURLOPT_POSTFIELDS => http_build_query($post_data),
  ]);

error_log($res);

$res = $mu->get_contents(
  'http://www.calc-site.com/calendars/solar_year?from_year=2020',
  [CURLOPT_POST => TRUE,
   CURLOPT_POSTFIELDS => http_build_query($post_data),
  ]);

error_log($res);

?>
