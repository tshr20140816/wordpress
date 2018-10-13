<?php

for ($i = 0; $i < 60; $i++) {
  $timestamp = strtotime('+' . ($i + 10) . ' days');
  $diff = date_diff($timestamp, date());
  error_log($diff['m']);
}

?>
