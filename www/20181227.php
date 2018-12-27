<?php

error_log(getmypid() . ' START');

$pid = pcntl_fork();
if ($pid == -1) {
     error_log('not fork');
} else if ($pid) {
    // 親プロセスの場合
    error_log('parent process');
    pcntl_wait($status); // ゾンビプロセスから守る
    error_log('$status : ' . $status);
} else {
    // 子プロセスの場合
    error_log('child process');
}

error_log(getmypid() . ' FINISH');
