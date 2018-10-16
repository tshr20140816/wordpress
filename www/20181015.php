<?php

$yyyy_limit = date('Y', strtotime('+3 years'));
error_log($yyyy_limit);

for ($i = 0; $i < 1096 - 80; $i++) {
  $timestamp = strtotime('+' . ($i + 80) . ' days');
  $yyyy = date('Y', $timestamp);
  if ($yyyy_limit == $yyyy) {
    break;
  }
  $d = date('j', $timestamp);
  if ($d == 1 || $d == 11 || $d == 21) {
    error_log(date('m/d', $timestamp));
  }
}

?>
