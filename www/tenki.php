<?php

$res = file_get_contents('https://tenki.jp/week/' . getenv('LOCATION_NUMBER') . '/');

$rc = preg_match('/announce_datetime:(\d+-\d+-\d+)/', $res, $matches);

error_log($matches[0]);
error_log($matches[1]);

$dt = $matches[1];

$tmp = explode(getenv('POINT_NAME'), $res);
$tmp = explode('<td class="forecast-wrap">', $tmp[1]);

$list_yobi = array('日', '月', '火', '水', '木', '金', '土');
$list_weather = [];
for ($i = 0; $i < 10; $i++) {
  $list = explode("\n", str_replace(' ', '', trim(strip_tags($tmp[$i + 1]))));
  $tmp2 = $list[0];
  $tmp2 = str_replace('晴', '☀', $tmp2);
  $tmp2 = str_replace('曇', '☁', $tmp2);
  $tmp2 = str_replace('雨', '☂', $tmp2);
  $tmp2 = str_replace('のち', '/', $tmp2);
  $tmp2 = str_replace('時々', '|', $tmp2);
  $tmp2 = str_replace('一時', '|', $tmp2);
  error_log(date('w', strtotime($dt . ' +' . $i . ' day')));
  error_log(date('m/d', strtotime($dt . ' +' . $i . ' day')) . ' # ' . $tmp2 . ' ' . $list[2] . ' ' . $list[1]. ' #');
  $list_weather[] = '{"title":"' . date('m/d', strtotime($dt . ' +' . $i . ' day')) . ' # ' . $tmp2 . ' ' . $list[2] . ' ' . $list[1]. ' #","duedate":"' . strtotime($dt . ' +' . $i . ' day') . '","tag":"WEATHER"}';
  
  
}

exit();

//$url = 'https://api.toodledo.com/3/account/authorize.php?response_type=code&client_id=' . getenv('TOODLEDO_CLIENTID') . '&state=' . getenv('TOODLEDO_SECRET') . '&scope=tasks';
//$res = file_get_contents($url);
//error_log($res);

$code = $_GET['code'];
$state = $_GET['state'];

error_log($code);
error_log($state);

$post_data = ['grant_type' => 'authorization_code', 'code' => $code];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://' . getenv('TOODLEDO_CLIENTID') . ':' . getenv('TOODLEDO_SECRET') . '@api.toodledo.com/3/account/token.php'); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_POST, TRUE);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
$res = curl_exec($ch);
curl_close($ch);

error_log($res);

$params = json_decode($res, TRUE);
error_log($params['access_token']);

$res = file_get_contents('https://api.toodledo.com/3/tasks/get.php?access_token=' . $params['access_token'] . '&comp=0&fields=tag');
//error_log($res);

$tasks = json_decode($res, TRUE);
//error_log(print_r($tasks, TRUE));
$list_delete_task = [];
for ($i = 0; $i < count($tasks); $i++) {
  if (array_key_exists('id', $tasks[$i]) && array_key_exists('tag', $tasks[$i])) {
    if ($tasks[$i]['tag'] == 'WEATHER') {
      $list_delete_task[] = $tasks[$i]['id'];
      error_log($tasks[$i]['id']);
      if (count($list_delete_task) == 50) {
        break;
      }
    }
  }
}
error_log(count($list_delete_task));

if (count($list_delete_task) > 0) {
  $post_data = ['access_token' => $params['access_token'], 'tasks' => '[' . implode(',', $list_delete_task) . ']'];
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, 'https://api.toodledo.com/3/tasks/delete.php'); 
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_POST, TRUE);
  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
  $res = curl_exec($ch);
  curl_close($ch);
  error_log($res);
}

$res = file_get_contents('https://api.toodledo.com/3/folders/get.php?access_token=' . $params['access_token']);
$folders = json_decode($res, TRUE);

$weather_folder_id = 0;
for ($i = 0; $i < count($folders); $i++) {
  if ($folders[$i]['name'] == 'WEATHER') {
    $weather_folder_id = $folders[$i]['id'];
    break;
  }
}

// $post_data = ['access_token' => $params['access_token'], 'tasks' => '[{"title":"' . $list_weather[0] . '"}]'];
$tmp = implode(',', $list_weather);
$tmp = str_replace('"tag":"WEATHER"', '"tag":"WEATHER","folder":"' . $weather_folder_id . '"', $tmp);
$post_data = ['access_token' => $params['access_token'], 'tasks' => '[' . $tmp . ']'];

error_log(http_build_query($post_data));

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.toodledo.com/3/tasks/add.php'); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_POST, TRUE);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
$res = curl_exec($ch);
curl_close($ch);

error_log($res);

exit();

?>
