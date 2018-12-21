<?php

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s'));

$ueragent = $_SERVER['HTTP_USER_AGENT'];
error_log("${pid} START USER AGENT : ${ueragent}");

$file = '/tmp/toodledo_vcalendar.ics';

header('Content-Type: text/calendar');
if (file_exists($file) && $ueragent == getenv('USER_AGENT_ICS')) {
  $res = file_get_contents($file);
  echo $res;
} else {
  echo "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nEND:VCALENDAR";
}

error_log("${pid} FINISH");
exit();

?>
