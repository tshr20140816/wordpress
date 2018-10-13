<?php

for ($i = 0; $i < 60; $i++) {
  $timestamp = strtotime('+' . ($i + 10) . ' days');
  $diff = $timestamp - time();
  error_log($diff);
}

?>
