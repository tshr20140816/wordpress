<?php

error_log(getmypid() . ' START');

$pid = pcntl_fork();
if ($pid == -1) {
     error_log('fork できません');
} else if ($pid) {
     // 親プロセスの場合
    error_log('parent process');
    pcntl_wait($status); // ゾンビプロセスから守る
} else {
    // 子プロセスの場合
    error_log('child process');
}

error_log(getmypid() . ' FINISH');
