<?php

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s'));

$file = '/tmp/toodledo_vcalendar.ics';

header('Content-Type: text/calendar');
if (file_exists($file)) {
  $res = file_get_contents($file);
  echo $res;
} else {
  echo "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nEND:VCALENDAR";
}

error_log("${pid} FINISH");
exit();

?>
