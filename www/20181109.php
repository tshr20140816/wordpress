<?php

$api_key = getenv('API_KEY');
$url = 'https://api.heroku.com/account';
  
$res = $mu->get_contents($url,
                         ['Accept: application/vnd.heroku+json; version=3',
                          "Authorization: Bearer ${api_key}",
                          'Connection: Keep-Alive',
                         ]);

$data = json_decode($res, true);

?>
