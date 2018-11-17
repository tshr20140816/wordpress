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
    . '","tag":"WEATHER","folder":"' . $folder_id_label . '"}';
}

error_log("${pid} FINISH");
?>
