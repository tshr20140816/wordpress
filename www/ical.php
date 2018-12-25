<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s'));

$ueragent = $_SERVER['HTTP_USER_AGENT'];
error_log("${pid} USER AGENT : ${ueragent}");

$mu = new MyUtils();

header('Content-Type: text/calendar');
if ($ueragent != getenv('USER_AGENT_ICS') && $requesturi != '/ical.php') {
    error_log("${pid} USER AGENT NG");
    echo "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nEND:VCALENDAR";
    exit();
}

$pdo = $mu->get_pdo();

$sql = 'SELECT T1.ical_data FROM t_ical T1';
$ical_data = '';
foreach ($pdo->query($sql) as $row) {
    $ical_data = $row['ical_data'];
    break;
}

$pdo = null;

if ($ical_data == '') {
    error_log("${pid} DATA NONE");
    echo "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nEND:VCALENDAR";
} else {
    error_log("${pid} OK");
    echo gzdecode(base64_decode($ical_data));
}

error_log("${pid} FINISH");
exit();
