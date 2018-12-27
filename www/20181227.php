<?php

error_log(getmypid() . ' START');

$pid = pcntl_fork();

error_log(getmypid() . ' FINISH');
