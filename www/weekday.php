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

// Get Tasks

$res = get_contents('https://api.toodledo.com/3/tasks/get.php?access_token=' . $access_token . '&comp=0&fields=duedate,context', NULL);
// error_log($res);

$tasks = json_decode($res, TRUE);
//error_log(print_r($tasks, TRUE));

$list_edit_task = [];
$edit_task_template = '{"id":"__ID__","context":"__CONTEXT__"}';

for ($i = 0; $i < count($tasks); $i++) {
  if (array_key_exists('id', $tasks[$i])) {
    $real_context_id = $list_context_id[intval(date('w', $tasks[$i]['duedate']))];
    $task_context_id = $tasks[$i]['context'];
    if ($task_context_id == '0' || $task_context_id != $real_context_id) {
      error_log($pid . ' $tasks[$i] : ' . print_r($tasks[$i], TRUE));
      $tmp = str_replace('__ID__', $tasks[$i]['id'], $edit_task_template);
      $tmp = str_replace('__CONTEXT__', $real_context_id, $tmp);
      $list_edit_task[] = $tmp;
    }
  }
}
$list_edit_task = array_slice($list_edit_task, 0, 50);
error_log($pid . ' $list_edit_task : ' . print_r($list_edit_task, TRUE));

if (count($list_edit_task) == 0) {
  error_log("${pid} EDIT COUNT : 0");
  exit();
}

$tmp = implode(',', $list_edit_task);
$post_data = ['access_token' => $access_token, 'tasks' => "[${tmp}]", 'fields' => 'context'];

error_log($pid . ' $post_data : ' . print_r($post_data, TRUE));

$res = get_contents(
  'https://api.toodledo.com/3/tasks/edit.php',
  [CURLOPT_POST => TRUE,
   CURLOPT_POSTFIELDS => http_build_query($post_data),
  ]);
error_log("${pid} edit.php RESPONSE : ${res}");

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
