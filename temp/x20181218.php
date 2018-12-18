<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$url = 'http://www.motomachi-pa.jp/cgi/manku.pl?park_id=1&mode=pc';

$res = $mu->get_contents($url);

error_log(hash('sha512', $res));

?>
