<?php

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s'));

$ueragent = $_SERVER['HTTP_USER_AGENT'];
error_log("${pid} USER AGENT : ${ueragent}");

clearstatcache();

$file = '/tmp/toodledo_vcalendar.ics';

error_log("${pid} FILE EXISTS : " . file_exists($file) ? 'YES' : 'NO');

header('Content-Type: text/calendar');
if (file_exists($file) && $ueragent == getenv('USER_AGENT_ICS')) {
  error_log("${pid} OK");
  $res = file_get_contents($file);
  echo $res;
} else {
  error_log("${pid} NG");
  echo "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nEND:VCALENDAR";
}

error_log("${pid} FINISH");
exit();

?>
