<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

get_task_moon($mu);

function get_task_moon($mu_) {

  // Get Folders
  $folder_id_label = $mu_->get_folder_id('LABEL');
  // Get Contexts
  $list_context_id = $mu_->get_contexts();

  $timestamp = strtotime('+1 day');
  $yyyy = date('Y', $timestamp);
  $mm = date('m', $timestamp);

  $res = $mu_->get_contents('https://eco.mtk.nao.ac.jp/koyomi/dni/' . $yyyy . '/m' . getenv('AREA_ID') . $mm . '.html');

  $res = mb_convert_encoding($res, 'UTF-8', 'EUC-JP');

  $tmp = explode('<table ', $res);
  $tmp = explode('</table>', $tmp[1]);
  $tmp = explode('</tr>', $tmp[0]);
  array_shift($tmp);
  array_pop($tmp);
  
  error_log(print_r($tmp, TRUE));
    
  $list_add_task = [];
  $add_task_template = '{"title":"__TITLE__","duedate":"__DUEDATE__","context":"__CONTEXT__","tag":"WEATHER2","folder":"'
    . $folder_id_label . '"}';
  for ($i = 0; $i < count($tmp); $i++) {
    $rc = preg_match('/<tr><td.*?>' . substr(' ' . date('j', $timestamp), -2) . '<\/td>/', $tmp[$i]);
    if ($rc == 1) {
      $rc = preg_match('/.+?<\/td>.*?<td>(.+?)<\/td>.*?<td>.+?<\/td>.*?<td>.+?<\/td>.*?<td>.+?<\/td>.*?<td>(.+?)</', $tmp[$i], $matches);
      error_log(print_r($matches, TRUE));

      if (trim($matches[1]) != '--:--') {
        $tmp = date('m/d', $timestamp) . ' ' . substr('0' . trim($matches[1]), -5) . ' 月の出';
        $tmp = str_replace('__TITLE__', $tmp, $add_task_template);
        $tmp = str_replace('__DUEDATE__', $timestamp, $tmp);
        $tmp = str_replace('__CONTEXT__', $list_context_id[date('w', $timestamp)], $tmp);
        $list_add_task[] = $tmp;
      }
      
      if (trim($matches[2]) != '--:--') {
        $tmp = date('m/d', $timestamp) . ' ' . substr('0' . trim($matches[2]), -5) . ' 月の入り';
        $tmp = str_replace('__TITLE__', $tmp, $add_task_template);
        $tmp = str_replace('__DUEDATE__', $timestamp, $tmp);
        $tmp = str_replace('__CONTEXT__', $list_context_id[date('w', $timestamp)], $tmp);
        $list_add_task[] = $tmp;
      }

      break;
    }
  }
  error_log(getmypid() . ' MOON : ' . print_r($list_add_task, TRUE));
  return $list_add_task;
}
?>
