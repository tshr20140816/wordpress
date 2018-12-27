<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s'));

$mu = new MyUtils();

/*
$url = 'https://api.dropboxapi.com/2/file_requests/list';

$res = $mu->get_contents(
    $url,
    [CURLOPT_HTTPHEADER => ['Authorization: Basic ' . base64_encode(getenv('DROPBOX_APP_KEY') . ':' . getenv('DROPBOX_APP_SECRET'))],
    CURLOPT_POST => true,
    ]
);

error_log($res);
*/

$url = 'https://api.dropboxapi.com/2/auth/token/from_oauth1';

$post_data = ['oauth1_token' => 'qievr8hamyg6ndck', 'oauth1_token_secret' => 'qomoftv0472git7'];

$res = $mu->get_contents(
    $url,
    [CURLOPT_HTTPHEADER => ['Authorization: Basic ' . base64_encode(getenv('DROPBOX_APP_KEY') . ':' . getenv('DROPBOX_APP_SECRET')),
                           'Content-Type: application/json'
                           ],
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($post_data),
    ]
);

error_log($res);
/*
https://www.dropbox.com/developers/documentation/http/documentation

curl -X POST "https://api.dropbox.com/1/metadata/link" \
    --header "Authorization: Basic <base64(APP_KEY:APP_SECRET)>" \
    -d "link=https://www.dropbox.com/sh/748f94925f0gesq/AAAMSoRJyhJFfkupnAU0wXuva?dl=0"
*/
