<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s'));

const LIST_YOBI = array('日', '月', '火', '水', '木', '金', '土');

$mu = new MyUtils();

// Access Token
$access_token = $mu->get_access_token();

$list_add_task = [];

// Culture Center Tasks
$list_add_task = array_merge($list_add_task, get_task_culturecenter($mu));

exit();

function get_task_culturecenter($mu_) {

  // Get Folders
  $folder_id_private = $mu_->get_folder_id('PRIVATE');

  // Get Contexts
  $list_context_id = $mu_->get_contexts();

  $y = date('Y');
  $m = date('n');

  $list_add_task = [];
  for ($j = 0; $j < 2; $j++) {
    $url = 'http://www.cf.city.hiroshima.jp/saeki-cs/sche6_park/sche6.cgi?year=' . $y . '&mon=' . $m;

    $res = $mu_->get_contents($url);
    $res = mb_convert_encoding($res, 'UTF-8', 'SJIS');

    $tmp = explode('<col span=1 align=right>', $res);
    $tmp = explode('</table>', $tmp[1]);

    $rc = preg_match_all('/<tr .+?<b>(.+?)<.*?<td(.*?)<\/td><\/tr>/s', $tmp[0], $matches, PREG_SET_ORDER);

    for ($i = 0; $i < count($matches); $i++) {
      $timestamp = mktime(0, 0, 0, $m, $matches[$i][1], $y);
      if (date('Ymd') > date('Ymd', $timestamp)) {
        continue;
      }
      $tmp = $matches[$i][2];
      $tmp = preg_replace('/<font .+?>.+?>/', '', $tmp);
      $tmp = preg_replace('/bgcolor.+?>/', '', $tmp);
      $tmp = trim($tmp, " \t\n\r\0\t>");
      $tmp = str_replace('　', '', $tmp);
      $tmp = trim(str_replace('<br>', ' ', $tmp));
      if (strlen($tmp) == 0) {
        continue;
      }
      $list_add_task[] = '{"title":"' . date('m/d', $timestamp) . ' 文セ ★ ' . $tmp
        . '","duedate":"' . $timestamp
      . '","context":"' . $list_context_id[date('w', $timestamp)]
      . '","tag":"CULTURECENTER","folder":"' . $folder_id_private . '"}';
    }
    if ($m == 12) {
      $y++;
      $m = 1;
    } else {
      $m++;
    }
  }
  $count_task = count($list_add_task);
  $list_add_task[] = '{"title":"' . date('Y/m/d H:i:s', strtotime('+ 9 hours')) . '  Culture Center Task Add : ' . $count_task
    . '","context":"' . $list_context_id[date('w', mktime(0, 0, 0, 1, 4, 2018))]
    . '","duedate":"' . mktime(0, 0, 0, 1, 4, 2018) . '","folder":"' . $folder_id_private . '"}';
  error_log(getmypid() . ' [' . __METHOD__ . '] TASKS CULTURECENTER : ' . print_r($list_add_task, TRUE));

  return $list_add_task;
}
?>
