<?php

if (!isset($_GET['code']) || !isset($_GET['state'])) {
  $url = 'https://api.toodledo.com/3/account/authorize.php?response_type=code&client_id=' . getenv('TOODLEDO_CLIENTID') . '&state=' . uniqid() . '&scope=basic%20tasks%20notes%20write';
  header('Location: ' . $url, TRUE, 301);
  exit();
}

$code = $_GET['code'];
$state = $_GET['state'];

error_log($code);
error_log($state);

$post_data = ['grant_type' => 'authorization_code', 'code' => $code];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.toodledo.com/3/account/token.php'); 
curl_setopt($ch, CURLOPT_USERPWD, getenv('TOODLEDO_CLIENTID') . ':' . getenv('TOODLEDO_SECRET'));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_POST, TRUE);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
$res = curl_exec($ch);
curl_close($ch);

error_log($res);

$params = json_decode($res, TRUE);
$access_token = $params['access_token'];
$expires_in = $params['expires_in'];
$refresh_token = $params['refresh_token'];
$scope = $params['scope'];

$connection_info = parse_url(getenv('DATABASE_URL'));
$pdo = new PDO(
  "pgsql:host=${connection_info['host']};dbname=" . substr($connection_info['path'], 1),
  $connection_info['user'],
  $connection_info['pass']);
  
$sql = 'TRUNCATE TABLE m_authorization;';

$pdo->exec($sql);


?>
