<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s'));

$mu = new MyUtils();

$url = 'https://api.dropboxapi.com/2/file_requests/list';

$res = $mu->get_contents(
    $url,
    [CURLOPT_HTTPHEADER => ['Authorization: Basic ' . base64_encode(getenv('DROPBOX_APP_KEY') . ':' . getenv('DROPBOX_APP_SECRET'))],
    CURLOPT_POST => true,
    ]
);

error_log(base64_encode('aaaaaaaaaaaaaaa:bbbbbbbbbbbbbbb'));
/*
https://www.dropbox.com/developers/documentation/http/documentation

curl -X POST "https://api.dropbox.com/1/metadata/link" \
    --header "Authorization: Basic <base64(APP_KEY:APP_SECRET)>" \
    -d "link=https://www.dropbox.com/sh/748f94925f0gesq/AAAMSoRJyhJFfkupnAU0wXuva?dl=0"
*/
