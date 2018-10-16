<?php

$year = date('%Y', '+3 years');

for ($i = 0; $i < 1096 - 80; $i++) {
  $timestamp = strtotime('+' . ($i + 80) . ' days');
  $y = date('Y', $timestamp);
  if ($year == $y) {
    break;
  }
  $d = date('j', $timestamp);
  if ($d == 1 || $d == 11 || $d == 21) {
    error_log(date('m/d', $timestamp));
  }
}

?>
