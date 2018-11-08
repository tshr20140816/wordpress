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

// Get Tasks

$url = 'https://api.toodledo.com/3/tasks/get.php?comp=0&fields=folder,duedate&access_token=' . $access_token;
$res = $mu->get_contents($url);
// error_log($res);

$tasks = json_decode($res, TRUE);
// error_log(print_r($tasks, TRUE));

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

$list_additional_label = [];
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
    
    $tmp = '##### '
      . $list_yobi[date('w', $list_non_label[$i])] . '曜日 '
      . date('m/d', $list_non_label[$i])
      . ' ##### '
      . $yyyy;
    // error_log($tmp);
    $list_additional_label[] = '{"title":"' . $tmp
      . '","duedate":"' . $list_non_label[$i]
      . '","tag":"ADDITIONAL","folder":"' . $label_folder_id . '"}';
  }
}
error_log($pid . ' $list_additional_label : ' . print_r($list_additional_label, TRUE));

if (count($list_additional_label) > 0) {
  $list_additional_label = array_slice($list_additional_label, 0, 50);

  $tmp = implode(',', $list_additional_label);
  $post_data = ['access_token' => $access_token, 'tasks' => '[' . $tmp . ']'];

  $res = $mu->get_contents(
    'https://api.toodledo.com/3/tasks/add.php',
    [CURLOPT_POST => TRUE,
     CURLOPT_POSTFIELDS => http_build_query($post_data),
    ]);

  error_log($pid . ' add.php RESPONSE : ' . $res);
}

error_log("${pid} FINISH");

$res = $mu->get_contents(
  'https://' . getenv('HEROKU_APP_NAME') . '.herokuapp.com/weekday.php',
  [CURLOPT_USERPWD => getenv('BASIC_USER') . ':' . getenv('BASIC_PASSWORD'),
  ]);

exit();
?>
