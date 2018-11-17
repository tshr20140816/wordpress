<?php
include(dirname(__FILE__) . '/../classes/MyUtils.php');

$connection_info = parse_url(getenv('DATABASE_URL'));
$pdo = new PDO(
  "pgsql:host=${connection_info['host']};dbname=" . substr($connection_info['path'], 1),
  $connection_info['user'],
  $connection_info['pass']);

$pdo->query('UPDATE m_authorization SET access_token = 'dummy';');

$pdo = NULL;

$mu = new MyUtils();

$access_token = $mu->get_access_token();

error_log('FINAL : ' . $access_token);
?>
