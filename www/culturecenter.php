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

$private_folder_id = 0;
for ($i = 0; $i < count($folders); $i++) {
  if ($folders[$i]['name'] == 'PRIVATE') {
    $private_folder_id = $folders[$i]['id'];
    error_log("${pid} PRIVATE FOLDER ID : ${private_folder_id}");
    break;
  }
}

// Get Tasks

$url = 'https://api.toodledo.com/3/tasks/get.php?comp=0&fields=tag&access_token=' . $access_token
  . '&after=' . strtotime('-2 day');
$res = get_contents($url, NULL);

$tasks = json_decode($res, TRUE);

$list_delete_task = [];
for ($i = 0; $i < count($tasks); $i++) {
  if (array_key_exists('id', $tasks[$i]) && array_key_exists('tag', $tasks[$i])) {
    if ($tasks[$i]['tag'] == 'CULTURECENTER') {
      $list_delete_task[] = $tasks[$i]['id'];
    }
  }
}
error_log($pid . ' $list_delete_task : ' . print_r($list_delete_task, TRUE));

// Get Contexts
$res = get_contents('https://api.toodledo.com/3/contexts/get.php?access_token=' . $access_token, NULL);
$contexts = json_decode($res, TRUE);
$list_context_id = [];
for ($i = 0; $i < count($contexts); $i++) {
  switch ($contexts[$i]['name']) {
    case '日......':
      $list_context_id[0] = $contexts[$i]['id'];
      break;
    case '.月.....':
      $list_context_id[1] = $contexts[$i]['id'];
      break;
    case '..火....':
      $list_context_id[2] = $contexts[$i]['id'];
      break;
    case '...水...':
      $list_context_id[3] = $contexts[$i]['id'];
      break;
    case '....木..':
      $list_context_id[4] = $contexts[$i]['id'];
      break;
    case '.....金.':
      $list_context_id[5] = $contexts[$i]['id'];
      break;
    case '......土':
      $list_context_id[6] = $contexts[$i]['id'];
      break;
  }
}
error_log($pid . ' $list_context_id : ' . print_r($list_context_id, TRUE));

// Culture Center

$y = date('Y');
$m = date('n');

$list_library = [];
for ($j = 0; $j < 2; $j++) {
  $url = 'http://www.cf.city.hiroshima.jp/saeki-cs/sche6_park/sche6.cgi?year=' . $y . '&mon=' . $m;
  error_log($pid . ' $url : ' . $url);
  
  $res = get_contents($url, NULL);
  $res = mb_convert_encoding($res, 'UTF-8', 'SJIS');

  // error_log($res);

  $tmp = explode('<col span=1 align=right>', $res);
  $tmp = explode('</table>', $tmp[1]);

  // error_log($tmp[0]);

  $rc = preg_match_all('/<tr .+?<b>(.+?)<.*?<td(.*?)<\/td><\/tr>/s', $tmp[0], $matches, PREG_SET_ORDER);
  // error_log(print_r($matches, TRUE));

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
    // error_log($tmp);
    $tmp = trim(str_replace('<br>', ' ', $tmp));
    if (strlen($tmp) == 0) {
      continue;
    }
    $list_library[] = '{"title":"' . date('m/d', $timestamp) . ' 文セ ★ ' . $tmp
      . '","duedate":"' . $timestamp
    . '","context":"' . $list_context_id[date('w', $timestamp)]
    . '","tag":"CULTURECENTER","folder":"' . $private_folder_id . '"}';
  }
  if ($m == 12) {
    $yyyy++;
    $m = 1;
  } else {
    $m++;
  }
}
error_log($pid . ' $list_library : ' . print_r($list_library, TRUE));

// Add Tasks

$post_data = ['access_token' => $access_token, 'tasks' => '[' . implode(',', $list_library) . ']'];

// error_log(http_build_query($post_data));

$res = get_contents(
  'https://api.toodledo.com/3/tasks/add.php',
  [CURLOPT_POST => TRUE,
   CURLOPT_POSTFIELDS => http_build_query($post_data),
  ]);

error_log("${pid} add.php RESPONSE : ${res}");

$count_add = substr_count($res, '"completed":0');

$tmp = '[{"title":"' . date('Y/m/d H:i:s', strtotime('+ 9 hours')) . ' ' . $requesturi . " Add : " . $count_add
  . '","duedate":"' . mktime(0, 0, 0, 1, 1, 2018). '"}]';
$post_data = ['access_token' => $access_token, 'tasks' => $tmp];

$res = get_contents(
  'https://api.toodledo.com/3/tasks/add.php',
  [CURLOPT_POST => TRUE,
   CURLOPT_POSTFIELDS => http_build_query($post_data),
  ]);

error_log("${pid} add.php RESPONSE : ${res}");

// Delete Tasks

error_log("${pid} DELETE TARGET TASK COUNT : " . count($list_delete_task));

if (count($list_delete_task) > 0) {
  $post_data = ['access_token' => $access_token, 'tasks' => '[' . implode(',', $list_delete_task) . ']'];  
  $res = get_contents(
    'https://api.toodledo.com/3/tasks/delete.php',
    [CURLOPT_POST => TRUE,
     CURLOPT_POSTFIELDS => http_build_query($post_data),
    ]);
  error_log("${pid} delete.php RESPONSE : ${res}");
}

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
