<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi}");

const LIST_YOBI = array('日', '月', '火', '水', '木', '金', '土');

$mu = new MyUtils();

// Access Token
$access_token = $mu->get_access_token();

// Get Contexts
$list_context_id = $mu->get_contexts();

// Get Folders
$folder_id_label = $mu->get_folder_id('LABEL');

$list_add_task = [];

// amedas
$res = $mu->get_contents('http://www.jma.go.jp/jp/amedas_h/today-' . getenv('AMEDAS') . '.html');

$tmp = explode('<td class="time left">時</td>', $res);
$tmp = explode('</table>', $tmp[1]);

$rc = preg_match_all('/<tr>(.*?)<td(.*?)>(.+?)<\/td>(.*?)' . str_repeat('<td(.*?)>(.+?)<\/td>', 7) . '(.+?)<\/tr>/s'
                     , $tmp[0], $matches, PREG_SET_ORDER);

$title = '';
for ($i = 0; $i < count($matches); $i++) {
  $hour = $matches[$i][3];
  $temp = $matches[$i][6];
  $rain = $matches[$i][8];
  $wind = $matches[$i][10] . $matches[$i][12];
  $humi = $matches[$i][16];
  $pres = $matches[$i][18];
  if ($temp == '&nbsp;') {
    continue;
  }
  error_log("${pid} ${hour}時 ${temp}℃ ${humi}% ${rain}mm ${wind}m/s ${pres}hPa");
  $title = "${hour}時 ${temp}℃ ${humi}% ${rain}mm ${wind}m/s ${pres}hPa";
}

if ($title != '') {
  $list_add_task[] = '{"title":"' . $title
    . '","duedate":"' . mktime(0, 0, 0, 1, 2, 2018)
    . '","context":"' . $list_context_id[date('w', mktime(0, 0, 0, 1, 2, 2018))]
    . '","tag":"WEATHER3","folder":"' . $folder_id_label . '"}';
}

// Rainfall

$url = 'https://map.yahooapis.jp/weather/V1/place?interval=5&output=json&appid=' . getenv('YAHOO_API_KEY')
  . '&coordinates=' . getenv('LONGITUDE') . ',' . getenv('LATITUDE');
$res = $mu->get_contents($url);

$data = json_decode($res, TRUE);
$data = $data['Feature'][0]['Property']['WeatherList']['Weather'];

$list = [];
for ($i = 0; $i < count($data); $i++) {
  if ($data[$i]['Rainfall'] != '0') {
    $list[] = substr($data[$i]['Date'], 8) . ' ' . $data[$i]['Rainfall'];
  }
}
if (count($list) > 0) {
  $tmp = date('H:m', strtotime('+9 hours')) . ' RAIN INFO : ' . implode(' ', $list);
} else {
  $tmp = date('H:m', strtotime('+9 hours')) . ' NO RAIN';
}
$list_add_task[] = '{"title":"' . $tmp
    . '","duedate":"' . mktime(0, 0, 0, 1, 1, 2018)
    . '","context":"' . $list_context_id[date('w', mktime(0, 0, 0, 1, 1, 2018))]
    . '","tag":"WEATHER3","folder":"' . $folder_id_label . '"}';

// Get Tasks
$url = 'https://api.toodledo.com/3/tasks/get.php?comp=0&fields=tag,duedate,context&access_token=' . $access_token
  . '&after=' . strtotime('-1 day');
$res = $mu->get_contents($url);
$tasks = json_decode($res, TRUE);

$list_delete_task = [];
for ($i = 0; $i < count($tasks); $i++) {
  if (array_key_exists('id', $tasks[$i]) && array_key_exists('tag', $tasks[$i])) {
    if ($tasks[$i]['tag'] == 'WEATHER3') {
      $list_delete_task[] = $tasks[$i]['id'];
    }
  }
}
error_log($pid . ' $list_delete_task : ' . print_r($list_delete_task, TRUE));

// Add Tasks
$rc = $mu->add_tasks($list_add_task);

// Delete Tasks
$mu->delete_tasks($list_delete_task);

error_log("${pid} FINISH");
?>
