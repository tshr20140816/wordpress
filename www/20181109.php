<?php

file_put_contents('/tmp/test', 'TEST');

$timestamp = filemtime('/tmp/test');

error_log(date('H:i:s'));
error_log(date('H:i:s', $timestamp));

?>
