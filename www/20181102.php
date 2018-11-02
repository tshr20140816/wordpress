<?php

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi}");

const LIST_YOBI = array('日', '月', '火', '水', '木', '金', '土');

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
  $pdo = null;
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

// Get Tasks

$res = get_contents('https://api.toodledo.com/3/tasks/get.php?comp=0&fields=duedate&access_token=' . $access_token, NULL);
// error_log($res);

$tasks = json_decode($res, TRUE);
// error_log(print_r($tasks, TRUE));

$list_edit_task = [];
$edit_task_template = '{"id":"__ID__","duedate":"__DUEDATE__"}';
for ($i = 0; $i < count($tasks); $i++) {
  if (array_key_exists('duedate', $tasks[$i]) && $tasks[$i]['duedate'] == 0) {
    error_log(print_r($tasks[$i], TRUE));
    $tmp = $tasks[$i]['title'];
    if (strlen($tmp) < 5 && preg_match('/\d\d\/\d\d /', $tmp) != 1) {
      continue;
    }
    $tmp = explode('/', $tmp);
    $mm = $tmp[0];
    $dd = $tmp[1];
    $yyyy = date('Y');
    if (date('md') > ($mm . $dd)) {
      $yyyy++;
    }
    if (!checkdate($mm, $dd, $yyyy)) {
      continue;
    }
    $tmp = str_replace('__ID__', $tasks[$i]['id'], $edit_task_template);
    $tmp = str_replace('__DUEDATE__', mktime(0, 0, 0, $mm, $dd, $yyyy), $tmp);
    $list_edit_task[] = $tmp;
  }
}
error_log($pid . ' $list_edit_task : ' . print_r($list_edit_task, TRUE));

error_log("${pid} FINISH ${requesturi}");

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
