<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$post_data = ['year' => 2018,
              'month' => 11,
              'day' => 30,
              'id' => getenv('AREA_ID'),
              'twil' => 0];

$res = $mu->get_contents(
  'https://eco.mtk.nao.ac.jp/cgi-bin/koyomi/sunmoon.cgi',
  [CURLOPT_POST => TRUE,
   CURLOPT_POSTFIELDS => http_build_query($post_data),
  ]);

error_log($res);

?>
