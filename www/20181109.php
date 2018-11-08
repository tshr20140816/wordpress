<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$mu = new MyUtils();

$api_key = getenv('API_KEY');
$url = 'https://api.heroku.com/account';

$res = $mu->get_contents(
  $url,
  [CURLOPT_HTTPHEADER => ['Accept: application/vnd.heroku+json; version=3',
                          "Authorization: Bearer ${api_key}",
                         ]]);

$data = json_decode($res, TRUE);

$url = "https://api.heroku.com/accounts/${data['id']}/actions/get-quota";

$res = $mu->get_contents(
  $url,
  [CURLOPT_HTTPHEADER => ['Accept: application/vnd.heroku+json; version=3.account-quotas',
                          "Authorization: Bearer ${api_key}",
                         ]]);

$data = json_decode($res, TRUE);

$dyno_used = (int)$data['quota_used'];
$dyno_quota = (int)$data['account_quota'];

error_log('$dyno_used : ' . $dyno_used);
error_log('$dyno_quota : ' . $dyno_quota);

$tmp = $dyno_quota - $dyno_used;
$tmp = floor($tmp / 86400) . 'd ' . ($tmp / 3600 % 24) . 'h ' . ($tmp / 60 % 60) . 'm';
error_log($tmp);
?>
