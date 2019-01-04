<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
$time_start = time();
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s', $time_start));

$mu = new MyUtils();

$rc = func001($mu);

function func001($mu_) {
    
    // cache search on list    
    $urls_is_cache['https://api.heroku.com/account'] =
        [CURLOPT_HTTPHEADER => ['Accept: application/vnd.heroku+json; version=3',
                                'Authorization: Bearer ' . getenv('HEROKU_API_KEY'),
                               ]];

    // cache search off list    
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
    
    foreach ($urls_is_cache as $url => $options) {
        if (array_key_exists(base64_encode($url), $cache_data)) {
            $results[$url] = $cache_data[$url];
        } else {
            $urls[$url] = $options;
        }
    }
    
    $mh = curl_multi_init();
    
    foreach ($urls as $url => $options_add) {
        error_log('CURL ADD URL : ' . $url);
        $ch = curl_init();
        $options = [CURLOPT_URL => $url,
                    CURLOPT_USERAGENT => getenv('USER_AGENT'),
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_FOLLOWLOCATION => 1,
                    CURLOPT_MAXREDIRS => 3,
                    CURLOPT_SSL_FALSESTART => true,
        ];
        curl_setopt_array($ch, $options);
        if (is_null($options_add) == false) {
            curl_setopt_array($ch, $options_add);
        }
        curl_multi_add_handle($mh, $ch);
        $list_ch[$url] = $ch;
    }
    
    $active = null;
    $rc = curl_multi_exec($mh, $active);
    
    while ($active && $rc == CURLM_OK) {
        if (curl_multi_select($mh) == -1) {
            usleep(1);
        }
        $rc = curl_multi_exec($mh, $active);
    }
    
    foreach (array_keys($urls) as $url) {
        $ch = $list_ch[$url];
        $res = curl_getinfo($ch);
        if ($res['http_code'] == 200) {
            $result = curl_multi_getcontent($ch);
            $results[$url] = $result;
        }
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
        // error_log(print_r($res, true));
    }
    
    curl_multi_close($mh);
 
    foreach ($results as $url => $result) {
        error_log('CURL RESULT URL : ' . $url);
    }
}
