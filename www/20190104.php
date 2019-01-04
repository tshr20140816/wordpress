<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
$time_start = time();
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s', $time_start));

$mu = new MyUtils();

$rc = func001($mu);

function func001($mu_) {
    
    // cache search on
    
    $urls_is_cache['https://api.heroku.com/account'] =
        [CURLOPT_HTTPHEADER => ['Accept: application/vnd.heroku+json; version=3',
                                'Authorization: Bearer ' . getenv('HEROKU_API_KEY'),
                               ]];

    // cache search off
    
    $urls[$mu_->get_env('URL_AMEDAS')] = null;
    
    $sql = <<< __HEREDOC__
SELECT T1.url_base64
      ,T1.content_compress_base64
  FROM t_webcache T1
 WHERE CASE WHEN LOCALTIMESTAMP < T1.update_time + interval '1 days' THEN 0 ELSE 1 END = 0
__HEREDOC__;

    $pdo = $mu_->get_pdo();
    $statement = $pdo->prepare($sql);
    $statement->execute();
    $results = $statement->fetchAll();
    
    $pdo = null;
    
    foreach ($results as $result) {
        $cache_data[$result['url_base64']] = $result['content_compress_base64'];
    }
    
    $results = [];
    
    // error_log(print_r($cache_data, true));
    
    foreach ($urls_is_cache in $url => $options) {
        if (array_key_exists(base64_encode($url), $cache_data)) {
            $results[$url] = $cache_data[$url];
        } else {
            $urls[$url] = $options;
        }
    }
    
    $mh = curl_multi_init();
}
