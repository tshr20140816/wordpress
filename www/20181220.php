<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();

$mu = new MyUtils();

$access_token = $mu->get_access_token();
$url = 'https://api.toodledo.com/3/tasks/get.php?comp=0&fields=tag,duedate,context,star,folder&access_token=' . $access_token;
$res = $mu->get_contents($url);

error_log($pid . ' TASKS (GZIP) : ' . strlen(gzencode($res, 9)));

$pdo = $mu->get_pdo();

$sql = "INSERT INTO t_test (data) VALUES ('" . gzencode($res, 9) . "')";

error_log($pid . ' ' . $sql);

$statement = $pdo->prepare($sql);
$rc = $statement->execute();

error_log($pid . ' ' . $rc);

$pdo = null;

?>
