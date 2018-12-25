<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');
$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];

error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s'));

$mu = new MyUtils();

const LIST_WAFU_GETSUMEI = array('', '睦月', '如月', '弥生', '卯月', '皐月', '水無月', '文月', '葉月', '長月', '神無月', '霜月', '師走');

for ($y = date('Y'); $y < date('Y') + 3; $y++) {
    for ($m = 1; $m < 13; $m++) {
        $timestamp = mktime(0, 0, 0, $m, 1, $y);
        error_log($pid . date('Ymd', $timestamp) . ' ' . LIST_WAFU_GETSUMEI[$m] . ' ' . date('F', $timestamp));
    }
}
