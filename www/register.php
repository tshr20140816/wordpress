<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi}");

if (!isset($_GET['code']) || !isset($_GET['state'])) {
    $url = 'https://api.toodledo.com/3/account/authorize.php?response_type=code&client_id=' . getenv('TOODLEDO_CLIENTID') . '&state=' . uniqid() . '&scope=basic%20tasks%20notes%20write';
    header('Location: ' . $url, TRUE, 301);
    error_log("${pid} FINISH HTTP STATUS 301");
    exit();
}

$mu = new MyUtils();

$code = $_GET['code'];
$state = $_GET['state'];

error_log($pid . ' ' . $code . ' : ${code}');
error_log($pid . ' ' . $state . ' : ${state}');

$post_data = ['grant_type' => 'authorization_code', 'code' => $code];

$res = $mu->get_contents(
    'https://api.toodledo.com/3/account/token.php',
    [CURLOPT_USERPWD => getenv('TOODLEDO_CLIENTID') . ':' . getenv('TOODLEDO_SECRET'),
     CURLOPT_POST => TRUE,
     CURLOPT_POSTFIELDS => http_build_query($post_data),
    ]);

error_log($pid . ' ' . $res . ' : ${res}');

$params = json_decode($res, TRUE);

$connection_info = parse_url(getenv('DATABASE_URL'));
$pdo = new PDO(
    "pgsql:host=${connection_info['host']};dbname=" . substr($connection_info['path'], 1),
    $connection_info['user'],
    $connection_info['pass']);
  
$sql = 'TRUNCATE TABLE m_authorization;';

$rc = $pdo->exec($sql);
error_log("${pid} TRUNCATE RESULT : ${rc}");

$sql = <<< __HEREDOC__
INSERT INTO m_authorization
( access_token
 ,expires_in
 ,refresh_token
 ,scope
) VALUES (
  :b_access_token
 ,:b_expires_in
 ,:b_refresh_token
 ,:b_scope
);
__HEREDOC__;
  
$statement = $pdo->prepare($sql);
$rc = $statement->execute(
    [':b_access_token' => $params['access_token'],
     ':b_expires_in' => $params['expires_in'],
     ':b_refresh_token' => $params['refresh_token'],
     ':b_scope' => $params['scope'],
    ]);
error_log("${pid} INSERT RESULT : ${rc}");

$pdo = null;

error_log("${pid} FINISH");
?>
