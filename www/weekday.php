<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

exit();

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi}");

$mu = new MyUtils();

// Access Token
$access_token = $mu->get_access_token();

// Get Contexts
$list_context_id = $mu->get_contexts();

// Get Tasks

$tasks = [];
$file_name = '/tmp/tasks_tenki';
if (file_exists($file_name)) {
  $timestamp = filemtime($file_name);
  if ($timestamp > strtotime('-5 minutes')) {
    $tasks = unserialize(file_get_contents($file_name));
    error_log($pid . ' CACHE HIT TASKS');
  }
}

if (count($tasks) == 0) {
  $url = 'https://api.toodledo.com/3/tasks/get.php?comp=0&fields=duedate,context&access_token=' . $access_token
    . '&after=' . strtotime('-2 day');
  $res = $mu->get_contents($url);
  $tasks = json_decode($res, TRUE);
}

$list_edit_task = [];
$edit_task_template = '{"id":"__ID__","context":"__CONTEXT__"}';

for ($i = 0; $i < count($tasks); $i++) {
  if (array_key_exists('id', $tasks[$i])) {
    $real_context_id = $list_context_id[intval(date('w', $tasks[$i]['duedate']))];
    $task_context_id = $tasks[$i]['context'];
    if ($task_context_id == '0' || $task_context_id != $real_context_id) {
      error_log($pid . ' $tasks[$i] : ' . print_r($tasks[$i], TRUE));
      $tmp = str_replace('__ID__', $tasks[$i]['id'], $edit_task_template);
      $tmp = str_replace('__CONTEXT__', $real_context_id, $tmp);
      $list_edit_task[] = $tmp;
    }
  }
}

if (count($list_edit_task) == 0) {
  error_log("${pid} EDIT COUNT : 0");
  exit();
}

$list_edit_task = array_slice($list_edit_task, 0, 50);
error_log($pid . ' $list_edit_task : ' . print_r($list_edit_task, TRUE));

// Edit Tasks

$tmp = implode(',', $list_edit_task);
$post_data = ['access_token' => $access_token, 'tasks' => "[${tmp}]", 'fields' => 'context'];

error_log($pid . ' $post_data : ' . print_r($post_data, TRUE));

$res = $mu->get_contents(
  'https://api.toodledo.com/3/tasks/edit.php',
  [CURLOPT_POST => TRUE,
   CURLOPT_POSTFIELDS => http_build_query($post_data),
  ]);
error_log("${pid} edit.php RESPONSE : ${res}");

error_log("${pid} FINISH");

exit();
?>
