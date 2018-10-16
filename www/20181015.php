<?php

$year = date('%Y');

for ($i = 0; $i < 10; $i++) {
  $timestamp = strtotime('+' . ($i + 80) . ' days');
  $d = date('j', $timestamp);
  error_log($d);
}

?>
