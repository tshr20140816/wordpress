<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi}");

$mu = new MyUtils();

// Access Token
$access_token = $mu->get_access_token();

// Get Folders
$label_folder_id = $mu->get_folder_id('LABEL');

// Get Contexts
$list_context_id = $mu->get_contexts();

// Get Tasks (all non complete tasks)

$url = 'https://api.toodledo.com/3/tasks/get.php?comp=0&fields=folder,duedate&access_token=' . $access_token;
$res = $mu->get_contents($url);

$tasks = json_decode($res, TRUE);

$list_label_task = [];
$list_schedule_task = [];
for ($i = 0; $i < count($tasks); $i++) {
  if (array_key_exists('duedate', $tasks[$i]) && array_key_exists('folder', $tasks[$i])) {
    if ($tasks[$i]['folder'] == $label_folder_id) {
      $list_label_task[] = $tasks[$i]['duedate'];
    } else {
      $list_schedule_task[] = $tasks[$i]['duedate'];
    }
  }
}

$list_non_label = array_unique(array_diff($list_schedule_task, $list_label_task));
sort($list_non_label);
error_log($pid . ' $list_non_label : ' . print_r($list_non_label, TRUE));

$list_add_task = [];
$list_yobi = array('日', '月', '火', '水', '木', '金', '土');
$subscript = '₀₁₂₃₄₅₆₇₈₉';
$timestamp = strtotime('+20 day');
for ($i = 0; $i < count($list_non_label); $i++) {
  if ($list_non_label[$i] > $timestamp) {
    // error_log(date('Y-m-d', $list_non_label[$i]));
    
    $yyyy = date('Y', $list_non_label[$i]);
    // To Small Size
    for ($j = 0; $j < 10; $j++) {
      $yyyy = str_replace($j, mb_substr($subscript, $j, 1), $yyyy);
    }
    
    $tmp = '### '
      . $list_yobi[date('w', $list_non_label[$i])] . '曜日 '
      . date('m/d', $list_non_label[$i])
      . ' ### '
      . $yyyy;
    $list_add_task[] = '{"title":"' . $tmp
      . '","duedate":"' . $list_non_label[$i]
      . '","context":"' . $list_context_id[date('w', $list_non_label[$i])]
      . '","tag":"ADDITIONAL","folder":"' . $label_folder_id . '"}';
  }
}
error_log($pid . ' $list_add_task : ' . print_r($list_add_task, TRUE));

// Add Tasks
$mu->add_tasks($list_add_task);

error_log("${pid} FINISH");

$res = $mu->get_contents(
  'https://' . getenv('HEROKU_APP_NAME') . '.herokuapp.com/weekday.php',
  [CURLOPT_USERPWD => getenv('BASIC_USER') . ':' . getenv('BASIC_PASSWORD'),
  ]);

exit();
?>
