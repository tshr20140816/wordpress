<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$file = '/tmp/parse.txt';
@unlink($file);

$url = 'https://' . getenv('HEROKU_APP_NAME') . '.herokuapp.com/20181216.php';

$options = [
  CURLOPT_TIMEOUT => 3,
  CURLOPT_USERPWD => getenv('BASIC_USER') . ':' . getenv('BASIC_PASSWORD'),
  ];

$res = $mu->get_contents($url, $options);

for ($i = 0; $i < 25; $i++) {
  if (file_exists($file) === TRUE) {
    break;
  }
  error_log('waiting');
  sleep(1);
}

error_log('PARENT PROCESS : ' . file_get_contents($file));

?>
