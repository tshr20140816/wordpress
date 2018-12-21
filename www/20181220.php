<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();

$mu = new MyUtils();

/*
$access_token = $mu->get_access_token();
$url = 'https://api.toodledo.com/3/tasks/get.php?comp=0&fields=tag,duedate,context,star,folder&access_token=' . $access_token;
$res = $mu->get_contents($url);

error_log($pid . ' TASKS (GZIP) : ' . strlen(gzencode($res, 9)));

$pdo = $mu->get_pdo();

$sql = "INSERT INTO t_test (data) VALUES ('" . base64_encode(gzencode($res, 9)) . "')";

error_log($pid . ' ' . $sql);

$statement = $pdo->prepare($sql);
$rc = $statement->execute();

error_log($pid . ' ' . $rc);

$pdo = null;
*/

$pdo = $mu->get_pdo();

$sql = 'SELECT data FROM t_test';

$data = '';
foreach ($pdo->query($sql) as $row) {
  $data = $row['data'];
  break;
}

$pdo = null;

$data = gzdecode(base64_decode($data));

$tasks = json_decode($data, TRUE);

error_log($pid . ' ' . count($tasks));

/*
BEGIN:VCALENDAR
VERSION:1.0
BEGIN:VEVENT
CATEGORIES:MEETING
STATUS:TENTATIVE
DTSTART:19960401T033000Z
DTEND:19960401T043000Z
SUMMARY:Your Proposal Review
DESCRIPTION:Steve and John to review newest proposal material
CLASS:PRIVATE
END:VEVENT
END:VCALENDAR
*/

$template_vevent = <<< __HEREDOC__
BEGIN:VEVENT
SUMMARY:__SUMMARY__
DTSTART;VALUE=DATE:__DTSTART__
DTEND;VALUE=DATE:__DTEND__
END:VEVENT
__HEREDOC__;

$folder_id_label = $mu->get_folder_id('LABEL');
$list_vevent = [];
for ($i = 0; $i < count($tasks); $i++) {
  if (array_key_exists('id', $tasks[$i])
      && array_key_exists('folder', $tasks[$i])
      && array_key_exists('dudate', $tasks[$i])
     ) {
    if ($folder_id_label == $tasks[$i]['folder']) {
      continue;
    }
    $tmp = $template_vevent;
    $tmp = str_replace('__SUMMARY__', $tasks[$i]['title'], $tmp);
    $tmp = str_replace('__DTSTART__', date('Ymd', $tasks[$i]['dudate']), $tmp);
    $tmp = str_replace('__DTEND__', date('Ymd', $tasks[$i]['dudate'] + 24 * 60 * 60), $tmp);
    $list_vevent[] = $tmp;
  }
}

error_log($pid . ' ' . count($list_vevent));
?>
