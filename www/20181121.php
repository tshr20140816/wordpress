<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$post_data = ['year' => '2018',
              'month' => '11',
              'day' => '30',
              'id' => getenv('AREA_ID'),
              'town' => getenv('AREA_ID'),
              'twil' => '0'];

$res = $mu->get_contents(
  'https://eco.mtk.nao.ac.jp/cgi-bin/koyomi/sunmoon.cgi',
  [CURLOPT_POST => TRUE,
   CURLOPT_POSTFIELDS => http_build_query($post_data),
  ]);

$res = mb_convert_encoding($res, 'UTF-8', 'EUC-JP');

error_log($res);
//<table class="koyomi" summary="Table">

$tmp = explode('<table class="koyomi" summary="Table">', $res);
//error_log($tmp[1]);

?>
