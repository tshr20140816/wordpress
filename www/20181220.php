<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();

$mu = new MyUtils();

/*
$access_token = $mu->get_access_token();
$url = 'https://api.toodledo.com/3/tasks/get.php?comp=0&fields=tag,duedate,context,star,folder&access_token=' . $access_token;
$res = $mu->get_contents($url);

error_log($pid . ' TASKS (GZIP) : ' . strlen(gzencode($res, 9)));

$pdo = $mu->get_pdo();

$sql = "INSERT INTO t_test (data) VALUES ('" . base64_encode(gzencode($res, 9)) . "')";

error_log($pid . ' ' . $sql);

$statement = $pdo->prepare($sql);
$rc = $statement->execute();

error_log($pid . ' ' . $rc);

$pdo = null;
*/

$pdo = $mu->get_pdo();

$sql = 'SELECT data FROM t_test';

$data = '';
foreach ($pdo->query($sql) as $row) {
  $data = $row['data'];
  break;
}

$pdo = null;

$data = gzdecode(base64_decode($data));

$tasks = json_decode($data, TRUE);

error_log($pid . ' ' . count($tasks));
?>
