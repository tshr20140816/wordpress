<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$url = 'http://the-outlets-hiroshima.com/static/detail/car';

$res = $mu->get_contents($url);

error_log($res);

$rc = preg_match('/<p id="parkingnow"><img src="(.+?)"/s', $res, $matches);

error_log(print_r($matches, TRUE));

$filePath = '/tmp/sample_image.jpg';
file_put_contents($filePath, file_get_contents($matches[1]));

$url = 'https://www.ocrwebservice.com/restservices/processDocument?language=japanese&outputformat=txt&gettext=true&getwords=true';

$session = curl_init();

$username = getenv('OCRWEBSERVICE_USER');
$license_code = getenv('OCRWEBSERVICE_LICENSE_CODE');
curl_setopt($session, CURLOPT_USERPWD, "$username:$license_code");

curl_setopt($session, CURLOPT_UPLOAD, true);
curl_setopt($session, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($session, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($session, CURLOPT_TIMEOUT, 200);
curl_setopt($session, CURLOPT_HEADER, false);
curl_setopt($session, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($session, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

$fp = fopen($filePath, 'r');
curl_setopt($session, CURLOPT_INFILESIZE, filesize($filePath));
$result = curl_exec($session);
curl_close($session);
fclose($fp);

$data = json_decode($result);

error_log(print_r($data, TRUE));
?>
