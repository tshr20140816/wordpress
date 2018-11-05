<?php

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi}");

// Access Token

$connection_info = parse_url(getenv('DATABASE_URL'));
$pdo = new PDO(
  "pgsql:host=${connection_info['host']};dbname=" . substr($connection_info['path'], 1),
  $connection_info['user'],
  $connection_info['pass']);

$sql = <<< __HEREDOC__
SELECT M1.access_token
      ,M1.refresh_token
      ,M1.expires_in
      ,M1.create_time
      ,M1.update_time
      ,CASE WHEN LOCALTIMESTAMP < M1.update_time + interval '90 minutes' THEN 0 ELSE 1 END refresh_flag
  FROM m_authorization M1;
__HEREDOC__;

$access_token = NULL;
foreach ($pdo->query($sql) as $row) {
  $access_token = $row['access_token'];
  $refresh_token = $row['refresh_token'];
  $refresh_flag = $row['refresh_flag'];
}

if ($access_token == NULL) {
  error_log("${pid} ACCESS TOKEN NONE");
  exit();
}

if ($refresh_flag == 1) {
  error_log("${pid} refresh_token : ${refresh_token}");
  $post_data = ['grant_type' => 'refresh_token', 'refresh_token' => $refresh_token];
  
  $res = get_contents(
    'https://api.toodledo.com/3/account/token.php',
    [CURLOPT_USERPWD => getenv('TOODLEDO_CLIENTID') . ':' . getenv('TOODLEDO_SECRET'),
     CURLOPT_POST => TRUE,
     CURLOPT_POSTFIELDS => http_build_query($post_data),
    ]);
  
  error_log("${pid} token.php RESPONSE : ${res}");
  $params = json_decode($res, TRUE);

  $sql = <<< __HEREDOC__
UPDATE m_authorization
   SET access_token = :b_access_token
      ,refresh_token = :b_refresh_token
      ,update_time = LOCALTIMESTAMP;
__HEREDOC__;

  $statement = $pdo->prepare($sql);
  $rc = $statement->execute([':b_access_token' => $params['access_token'],
                             ':b_refresh_token' => $params['refresh_token']]);
  error_log("${pid} UPDATE RESULT : ${rc}");
  
  $access_token = $params['access_token'];
}

$pdo = null;

// Get Folders

$res = get_contents('https://api.toodledo.com/3/folders/get.php?access_token=' . $access_token, NULL);
$folders = json_decode($res, TRUE);

$label_folder_id = 0;
for ($i = 0; $i < count($folders); $i++) {
  if ($folders[$i]['name'] == 'LABEL') {
    $label_folder_id = $folders[$i]['id'];
    error_log("${pid} LABEL FOLDER ID : ${label_folder_id}");
    break;
  }
}

// Get Tasks

$res = get_contents('https://api.toodledo.com/3/tasks/get.php?comp=0&fields=tag,folder,duedate&access_token=' . $access_token, NULL);
// error_log($res);

$tasks = json_decode($res, TRUE);
// error_log(print_r($tasks, TRUE));

$list_label_task = [];
for ($i = 0; $i < count($tasks); $i++) {
  if (array_key_exists('duedate', $tasks[$i]) && array_key_exists('folder', $tasks[$i])) {
    if ($tasks[$i]['folder'] == $label_folder_id) {
      $list_label_task[] = date('Ymd', $tasks[$i]['duedate']);
      // error_log($pid . ' ' . date('Y-m-d', $tasks[$i]['duedate']) . ' ' . $tasks[$i]['duedate']);
    }
  }
}
error_log($pid . ' $list_label_task : ' . print_r($list_label_task, TRUE));

//

$y = date('Y');
$m = date('n');

$list_library = [];
for ($j = 0; $j < 2; $j++) {
  $url = 'http://www.cf.city.hiroshima.jp/saeki-cs/sche6_park/sche6.cgi?year=' . $y . '&mon=' . $m;

  $res = $mu->get_contents($url, NULL);
  $res = mb_convert_encoding($res, 'UTF-8', 'SJIS');

  // error_log($res);

  $tmp = explode('<col span=1 align=right>', $res);
  $tmp = explode('</table>', $tmp[1]);

  // error_log($tmp[0]);

  $rc = preg_match_all('/<tr .+?<b>(.+?)<.*?<td(.*?)<\/td><\/tr>/s', $tmp[0], $matches, PREG_SET_ORDER);

  error_log(print_r($matches, TRUE));

  for ($i = 0; $i < count($matches); $i++) {
    $timestamp = mktime(0, 0, 0, $m, $matches[$i][1], $y);
    if (date('Ymd') > date('Ymd', $timestamp)) {
      continue;
    }
    $tmp = $matches[$i][2];
    $tmp = trim($tmp, " \t\n\r\0\t>");
    $tmp = preg_replace('/<font .+?>.+?>/', '', $tmp);
    $tmp = str_replace('　', '', $tmp);
    $tmp = preg_replace('/bgcolor.+?>/', '', $tmp);
    $tmp = trim(str_replace('<br>', ' ', $tmp));
    if (strlen($tmp) > 0) {
      $list_library[$timestamp] = date('m/d', $timestamp) . ' 文セ ★ ' . $tmp;
      // error_log(date('m/d', $timestamp) . ' 文セ ★ ' . $tmp);
    }
  }
  if ($m == 12) {
    $yyyy++;
    $m = 1;
  } else {
    $m++;
  }
}
error_log(print_r($list_library, TRUE));


error_log("${pid} FINISH");

exit();

function get_contents($url_, $options_) {
  $ch = curl_init();
  curl_setopt_array($ch, [
    CURLOPT_URL => $url_,
    CURLOPT_USERAGENT => getenv('USER_AGENT'),
    CURLOPT_RETURNTRANSFER => TRUE,
    CURLOPT_ENCODING => '',
    CURLOPT_FOLLOWLOCATION => 1,
    CURLOPT_MAXREDIRS => 3,
    CURLOPT_SSL_FALSESTART => TRUE,
    ]);
  if (is_null($options_) == FALSE) {
    curl_setopt_array($ch, $options_);
  }
  $res = curl_exec($ch);
  curl_close($ch);
  
  return $res;
}
?>
