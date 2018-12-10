<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$post_data = ['from_year' => 2019];

$res = $mu->get_contents(
  'http://www.calc-site.com/calendars/solar_year',
  [CURLOPT_POST => TRUE,
   CURLOPT_POSTFIELDS => http_build_query($post_data),
  ], TRUE);

?>
