<?php
include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$access_token = $mu->get_access_token();

error_log('FINAL : ' . $access_token);
?>
