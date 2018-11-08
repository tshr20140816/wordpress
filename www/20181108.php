<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi}");

$mu = new MyUtils();

// Access Token
$access_token = $mu->get_access_token();

// Get Folders
$folder_id_private = $mu->get_folder_id('PRIVATE');

// Soccer

$res = $mu->get_contents(getenv('SOCCER_TEAM_CSV_FILE'));
$res = mb_convert_encoding($res, 'UTF-8', 'SJIS');
// error_log($res);

$list_tmp = explode("\n", $res);
// error_log(print_r($list_tmp, TRUE));

$list_soccer = [];
$add_task_template = '{"title":"__TITLE__","duedate":"__DUEDATE__","tag":"SOCCER","folder":"' . $folder_id_private . '"}';
for ($i = 1; $i < count($list_tmp) - 1; $i++) {
  $tmp = explode(',', $list_tmp[$i]);
  $timestamp = strtotime(trim($tmp[1], '"'));
  if (date('Ymd') >= date('Ymd', $timestamp)) {
    continue;
  }
  // error_log(print_r($tmp, TRUE));
  $tmp1 = trim($tmp[2], '"');
  $rc = preg_match('/\d+:\d+:\d\d/', $tmp1);
  if ($rc == 1) {
    $tmp1 = substr($tmp1, 0, strlen($tmp1) - 3);
  }
  $tmp1 = substr(trim($tmp[1], '"'), 5) . ' ' . $tmp1 . ' ' . trim($tmp[0], '"') . ' ' . trim($tmp[6], '"');
  // error_log($tmp1);
  $tmp1 = str_replace('__TITLE__', $tmp1, $add_task_template);
  $tmp1 = str_replace('__DUEDATE__', $timestamp, $tmp1);
  $list_soccer[] = $tmp1;
}
error_log($pid . ' $list_soccer : ' . print_r($list_soccer, TRUE));

// Get Tasks

$res = $mu->get_contents('https://api.toodledo.com/3/tasks/get.php?comp=0&fields=tag&access_token=' . $access_token);
// error_log($res);

$tasks = json_decode($res, TRUE);
// error_log(print_r($tasks, TRUE));

$list_delete_task = [];
for ($i = 0; $i < count($tasks); $i++) {
  if (array_key_exists('tag', $tasks[$i]) && $tasks[$i]['tag'] == 'SOCCER') {
    $list_delete_task[] = $tasks[$i]['id'];
  }
}
error_log($pid . ' $list_delete_task : ' . print_r($list_delete_task, TRUE));

?>
