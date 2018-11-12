<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi}");

$mu = new MyUtils();

// Get quota

$api_key = getenv('API_KEY');
$url = 'https://api.heroku.com/account';

$res = $mu->get_contents(
  $url,
  [CURLOPT_HTTPHEADER => ['Accept: application/vnd.heroku+json; version=3',
                          "Authorization: Bearer ${api_key}",
                         ]]);

$data = json_decode($res, TRUE);

$url = "https://api.heroku.com/accounts/${data['id']}/actions/get-quota";

$res = $mu->get_contents(
  $url,
  [CURLOPT_HTTPHEADER => ['Accept: application/vnd.heroku+json; version=3.account-quotas',
                          "Authorization: Bearer ${api_key}",
                         ]]);

$data = json_decode($res, TRUE);

$dyno_used = (int)$data['quota_used'];
$dyno_quota = (int)$data['account_quota'];

error_log($pid . ' $dyno_used : ' . $dyno_used);
error_log($pid . ' $dyno_quota : ' . $dyno_quota);

// Access Token
$access_token = $mu->get_access_token();

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
  $url = 'https://api.toodledo.com/3/tasks/get.php?comp=0&fields=tag&access_token=' . $access_token
    . '&after=' . strtotime('-2 day');
  $res = $mu->get_contents($url);
  $tasks = json_decode($res, TRUE);
}

// Add Tasks

$tmp = $dyno_quota - $dyno_used;
$tmp = floor($tmp / 86400) . 'd ' . ($tmp / 3600 % 24) . 'h ' . ($tmp / 60 % 60) . 'm';
$tmp = '[{"title":"' . date('Y/m/d H:i:s', strtotime('+ 9 hours')) . ' quota : ' . $tmp
  . '","tag":"QUOTA","duedate":"' . mktime(0, 0, 0, 1, 1, 2018). '"}]';
$post_data = ['access_token' => $access_token, 'tasks' => $tmp];

$res = $mu->get_contents(
  'https://api.toodledo.com/3/tasks/add.php',
  [CURLOPT_POST => TRUE,
   CURLOPT_POSTFIELDS => http_build_query($post_data),
  ]);
error_log("${pid} add.php RESPONSE : ${res}");

error_log("${pid} FINISH");

$res = $mu->get_contents(
  'https://' . getenv('HEROKU_APP_NAME') . '.herokuapp.com/soccer.php',
  [CURLOPT_USERPWD => getenv('BASIC_USER') . ':' . getenv('BASIC_PASSWORD'),
  ]);

exit();
?>
