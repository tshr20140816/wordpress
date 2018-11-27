<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s'));

const LIST_YOBI = array('日', '月', '火', '水', '木', '金', '土');

$mu = new MyUtils();

// Access Token
$access_token = $mu->get_access_token();

// Get Folders
$folder_id_work = $mu->get_folder_id('WORK');

$list_add_task = [];

// amedas
$list_add_task = array_merge($list_add_task, get_task_amedas($mu));
  
// Rainfall
$list_add_task = array_merge($list_add_task, get_task_rainfall($mu));

// Get Tasks
$url = 'https://api.toodledo.com/3/tasks/get.php?comp=0&fields=tag,duedate,context,star,folder&access_token=' . $access_token
  . '&after=' . strtotime('-1 day');
$res = $mu->get_contents($url);
$tasks = json_decode($res, TRUE);

// 削除タスク抽出

$list_delete_task = [];
for ($i = 0; $i < count($tasks); $i++) {
  if (array_key_exists('id', $tasks[$i]) && array_key_exists('tag', $tasks[$i])) {
    if ($tasks[$i]['tag'] == 'WEATHER3') {
      $list_delete_task[] = $tasks[$i]['id'];
    }
  }
}
error_log($pid . ' $list_delete_task : ' . print_r($list_delete_task, TRUE));

// WORK & Star の日付更新

$list_edit_task = [];
$edit_task_template = '{"id":"__ID__","title":"__TITLE__"}';
for ($i = 0; $i < count($tasks); $i++) {
  if (array_key_exists('id', $tasks[$i]) && array_key_exists('folder', $tasks[$i])) {
    if ($tasks[$i]['folder'] == $folder_id_work && $tasks[$i]['star'] == '1') {
      $duedate = $tasks[$i]['duedate'];
      $title = $tasks[$i]['title'];
      if (substr($title, 0, 5) == date('m/d', $duedate)) {
        continue;
      }
      $tmp = str_replace('__ID__', $tasks[$i]['id'], $edit_task_template);
      $tmp = str_replace('__TITLE__', date('m/d', $duedate) . substr($title, 5), $tmp);
      $list_edit_task[] = $tmp;
    }
  }
}
error_log($pid . ' $list_edit_task : ' . print_r($list_edit_task, TRUE));

// Add Tasks
$rc = $mu->add_tasks($list_add_task);

// Edit Tasks
$rc = $mu->edit_tasks($list_edit_task);

// Delete Tasks
$mu->delete_tasks($list_delete_task);

error_log("${pid} FINISH");

function get_task_amedas($mu_) {
  
  // Get Folders
  $folder_id_label = $mu_->get_folder_id('LABEL');
  
  // Get Contexts
  $list_context_id = $mu_->get_contexts();
  
  $list_add_task = [];
  
  $res = $mu_->get_contents('http://www.jma.go.jp/jp/amedas_h/today-' . getenv('AMEDAS') . '.html');

  $tmp = explode('">時刻</td>', $res);
  $tmp = explode('</table>', $tmp[1]);

  $tmp1 = explode('</tr>', $tmp[0]);
  $headers = explode('</td>', $tmp1[0]);
  error_log($pid . ' $headers : ' . print_r($headers, TRUE));

  for ($i = 0; $i < count($headers); $i++) {
    switch (trim(strip_tags($headers[$i]))) {
      case '気温':
        $index_temp = $i + 2;
      case '降水量':
        $index_rain = $i + 2;
      case '風向':
        $index_wind = $i + 2;
      case '風速':
        $index_wind_speed = $i + 2;
      case '湿度':
        $index_humi = $i + 2;
      case '気圧':
        $index_pres = $i + 2;
    }
  }

  $rc = preg_match_all('/<tr>.*?<td.*?>(.+?)<\/td>.*?' . str_repeat('<td.*?>(.+?)<\/td>', count($headers) - 1) . '.+?<\/tr>/s'
                       , $tmp[0], $matches, PREG_SET_ORDER);
  array_shift($matches);

  $title = '';
  for ($i = 0; $i < count($matches); $i++) {
    $hour = $matches[$i][1];
    $temp = $matches[$i][$index_temp];
    $rain = $matches[$i][$index_rain];
    $wind = $matches[$i][$index_wind] . $matches[$i][$index_wind_speed];
    $humi = $matches[$i][$index_humi];
    $pres = $matches[$i][$index_pres];
    if ($temp == '&nbsp;') {
      continue;
    }
    // error_log(getmypid() . " ${hour}時 ${temp}℃ ${humi}% ${rain}mm ${wind}m/s ${pres}hPa");
    $title = "${hour}時 ${temp}℃ ${humi}% ${rain}mm ${wind}m/s ${pres}hPa";
  }

  if ($title != '') {
    $list_add_task[] = '{"title":"' . $title
      . '","duedate":"' . mktime(0, 0, 0, 1, 2, 2018)
      . '","context":"' . $list_context_id[date('w', mktime(0, 0, 0, 1, 2, 2018))]
      . '","tag":"WEATHER3","folder":"' . $folder_id_label . '"}';
  }
  
  error_log(getmypid() . ' TASKS AMEDAS : ' . print_r($list_add_task, TRUE));
  return $list_add_task;
}

function get_task_rainfall($mu_) {
  
  // Get Folders
  $folder_id_label = $mu_->get_folder_id('LABEL');
  
  // Get Contexts
  $list_context_id = $mu_->get_contexts();
  
  $list_add_task = [];
    
  $url = 'https://map.yahooapis.jp/geoapi/V1/reverseGeoCoder?output=json&appid=' . getenv('YAHOO_API_KEY')
    . '&lon=' . getenv('LONGITUDE') . '&lat=' . getenv('LATITUDE');
  $res = $mu_->get_contents($url);
  $data = json_decode($res, TRUE);
  error_log(getmypid() . ' $data : ' . print_r($data, TRUE));
  
  $url = 'https://map.yahooapis.jp/weather/V1/place?interval=5&output=json&appid=' . getenv('YAHOO_API_KEY')
    . '&coordinates=' . getenv('LONGITUDE') . ',' . getenv('LATITUDE');
  $res = $mu_->get_contents($url);

  $data = json_decode($res, TRUE);
  error_log(getmypid() . ' $data : ' . print_r($data, TRUE));
  $data = $data['Feature'][0]['Property']['WeatherList']['Weather'];

  $list = [];
  for ($i = 0; $i < count($data); $i++) {
    if ($data[$i]['Rainfall'] != '0') {
      $list[] = substr($data[$i]['Date'], 8) . ' ' . $data[$i]['Rainfall'];
    }
  }
  if (count($list) > 0) {
    $tmp = date('H:i', strtotime('+9 hours')) . ' RAIN INFO : ' . implode(' ', $list);
  } else {
    $tmp = date('H:i', strtotime('+9 hours')) . ' NO RAIN';
  }
  $list_add_task[] = '{"title":"' . $tmp
      . '","duedate":"' . mktime(0, 0, 0, 1, 1, 2018)
      . '","context":"' . $list_context_id[date('w', mktime(0, 0, 0, 1, 1, 2018))]
      . '","tag":"WEATHER3","folder":"' . $folder_id_label . '"}';
  
  error_log(getmypid() . ' TASKS RAINFALL : ' . print_r($list_add_task, TRUE));
  return $list_add_task;
}
?>
