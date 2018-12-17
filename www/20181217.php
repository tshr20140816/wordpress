<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$file = '/tmp/parse.txt';
@unlink($file);

$url = 'https://status.ocr.space/';
$res = $mu->get_contents($url);

$rc = preg_match('/LAST UPDATE (.+?)<.+?Free OCR API <span class="status {{ data.status }}">(.+?)<.+?Free OCR API <span class="status {{ data.status }}">(.+?)</s', $res, $matches);

error_log(print_r($matches, TRUE));
error_log($matches[2] . ' ' . $matches[1] . ' '. $matches[3]);

if (trim($matches[2]) == 'DOWN') {
  error_log('NO GOOD');
}

exit();

$url = 'https://' . getenv('HEROKU_APP_NAME') . '.herokuapp.com/20181216.php';

$options = [
  CURLOPT_TIMEOUT => 2,
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
