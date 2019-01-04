<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
$time_start = time();
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s', $time_start));

$mu = new MyUtils();

// cache search on list
$urls_is_cache['https://api.heroku.com/account'] =
    [CURLOPT_HTTPHEADER => ['Accept: application/vnd.heroku+json; version=3',
                            'Authorization: Bearer ' . getenv('HEROKU_API_KEY'),
                           ]];

// cache search off list
$urls[$mu->get_env('URL_AMEDAS')] = null;

// $rc = get_contents_multi($mu, $urls, $urls_is_cache);
$rc = $mu->get_contents_multi($urls, $urls_is_cache);

function get_contents_multi($mu_, $urls_, $urls_is_cache_) {

    $sql_select = <<< __HEREDOC__
SELECT T1.url_base64
      ,T1.content_compress_base64
  FROM t_webcache T1
 WHERE CASE WHEN LOCALTIMESTAMP < T1.update_time + interval '1 days' THEN 0 ELSE 1 END = 0
__HEREDOC__;

    $pdo = $mu_->get_pdo();
    $statement = $pdo->prepare($sql_select);
    $statement->execute();
    $results = $statement->fetchAll();

    foreach ($results as $result) {
        $cache_data[$result['url_base64']] = $result['content_compress_base64'];
    }

    $results_cache = [];

    // error_log(print_r($cache_data, true));

    foreach ($urls_is_cache_ as $url => $options) {
        if (array_key_exists(base64_encode($url), $cache_data)) {
            error_log(getmypid() . ' [' . __METHOD__ . '] (CACHE HIT) $url : ' . $url);
            $results_cache[$url] = gzdecode(base64_decode($cache_data[base64_encode($url)]));
        } else {
            $urls_[$url] = $options;
        }
    }

    $mh = curl_multi_init();

    foreach ($urls_ as $url => $options_add) {
        error_log(getmypid() . ' [' . __METHOD__ . '] CURL MULTI Add $url : ' . $url);
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

    $results = [];
    foreach (array_keys($urls_) as $url) {
        $ch = $list_ch[$url];
        $res = curl_getinfo($ch);
        if ($res['http_code'] == 200) {
            error_log(getmypid() . ' [' . __METHOD__ . '] CURL Result $url : ' . $url);
            $result = curl_multi_getcontent($ch);
            $results[$url] = $result;
        }
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }

    curl_multi_close($mh);

    $sql_delete = <<< __HEREDOC__
DELETE
  FROM t_webcache
 WHERE url_base64 = :b_url_base64
    OR LOCALTIMESTAMP > update_time + interval '5 days';
__HEREDOC__;

    $sql_insert = <<< __HEREDOC__
INSERT INTO t_webcache
( url_base64
 ,content_compress_base64
) VALUES (
  :b_url_base64
 ,:b_content_compress_base64
);
__HEREDOC__;

    foreach ($results as $url => $result) {
        if (array_key_exists($url, $urls_is_cache_) === false) {
            continue;
        }

        // delete & insert

        $url_base64 = base64_encode($url);
        $statement = $pdo->prepare($sql_delete);
        $rc = $statement->execute([':b_url_base64' => $url_base64]);
        error_log(getmypid() . ' [' . __METHOD__ . '] DELETE $rc : ' . $rc);

        $statement = $pdo->prepare($sql_insert);
        $rc = $statement->execute([':b_url_base64' => $url_base64,
                                   ':b_content_compress_base64' => base64_encode(gzencode($result, 9))]);
        error_log(getmypid() . ' [' . __METHOD__ . '] INSERT $rc : ' . $rc);
    }

    $pdo = null;

    $results = array_merge($results, $results_cache);

    error_log(getmypid() . ' [' . __METHOD__ . '] urls : ' . print_r(array_keys($results), true));

    return $results;
}
