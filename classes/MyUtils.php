<?php

class MyUtils
{
    private $_access_token;

    function get_pdo()
    {
        $connection_info = parse_url(getenv('DATABASE_URL'));
        return new PDO(
            "pgsql:host=${connection_info['host']};dbname=" . substr($connection_info['path'], 1),
            $connection_info['user'],
            $connection_info['pass']
        );
    }

    function get_access_token()
    {
        $log_prefix = getmypid() . ' [' . __METHOD__ . '] ';

        $file_name = '/tmp/access_token';

        if (file_exists($file_name)) {
            $timestamp = filemtime($file_name);
            if ($timestamp > strtotime('-15 minutes')) {
                $access_token = file_get_contents($file_name);
                error_log($log_prefix . '(CACHE HIT) $access_token : ' . $access_token);
                $this->$_access_token = $access_token;
                return $access_token;
            }
        }

        $sql = <<< __HEREDOC__
SELECT M1.access_token
      ,M1.refresh_token
      ,M1.expires_in
      ,M1.create_time
      ,M1.update_time
      ,CASE WHEN LOCALTIMESTAMP < M1.update_time + interval '90 minutes' THEN 0 ELSE 1 END refresh_flag
  FROM m_authorization M1;
__HEREDOC__;

        $pdo = $this->get_pdo();

        $access_token = null;
        foreach ($pdo->query($sql) as $row) {
            $access_token = $row['access_token'];
            $refresh_token = $row['refresh_token'];
            $refresh_flag = $row['refresh_flag'];
        }

        if ($access_token == null) {
            error_log($log_prefix . 'ACCESS TOKEN NONE');
            exit();
        }

        if ($refresh_flag == 0) {
            $res = $this->get_contents('https://api.toodledo.com/3/folders/get.php?access_token=' . $access_token);
            if ($res == '{"errorCode":2,"errorDesc":"Unauthorized","errors":[{"status":"2","message":"Unauthorized"}]}') {
                $refresh_flag = 1;
            } else {
                file_put_contents('/tmp/folders', serialize(json_decode($res, true)));
            }
        }

        if ($refresh_flag == 1) {
            error_log($log_prefix . "refresh_token : ${refresh_token}");
            $post_data = ['grant_type' => 'refresh_token', 'refresh_token' => $refresh_token];

            $res = $this->get_contents(
                'https://api.toodledo.com/3/account/token.php',
                [CURLOPT_USERPWD => getenv('TOODLEDO_CLIENTID') . ':' . getenv('TOODLEDO_SECRET'),
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($post_data),
                ]
            );

            error_log($log_prefix . "token.php RESPONSE : ${res}");
            $params = json_decode($res, true);

            $sql = <<< __HEREDOC__
UPDATE m_authorization
   SET access_token = :b_access_token
      ,refresh_token = :b_refresh_token
      ,update_time = LOCALTIMESTAMP;
__HEREDOC__;

            $statement = $pdo->prepare($sql);
            $rc = $statement->execute([':b_access_token' => $params['access_token'],
                                 ':b_refresh_token' => $params['refresh_token']]);
            error_log($log_prefix . "UPDATE RESULT : ${rc}");
            $access_token = $params['access_token'];
        }
        $pdo = null;

        error_log($log_prefix . '$access_token : ' . $access_token);

        $this->$_access_token = $access_token;
        file_put_contents($file_name, $access_token); // For Cache

        return $access_token;
    }

    function get_folder_id($folder_name_)
    {
        $log_prefix = getmypid() . ' [' . __METHOD__ . '] ';

        $file_name = '/tmp/folders';
        if (file_exists($file_name)) {
            $folders = unserialize(file_get_contents($file_name));
            error_log($log_prefix . '(CACHE HIT) FOLDERS');
        } else {
            $res = $this->get_contents('https://api.toodledo.com/3/folders/get.php?access_token=' . $this->$access_token, null, true);
            $folders = json_decode($res, true);
            file_put_contents($file_name, serialize($folders));
        }

        $target_folder_id = 0;
        for ($i = 0; $i < count($folders); $i++) {
            if ($folders[$i]['name'] == $folder_name_) {
                $target_folder_id = $folders[$i]['id'];
                error_log($log_prefix . "${folder_name_} FOLDER ID : ${target_folder_id}");
                break;
            }
        }
        return $target_folder_id;
    }

    function get_contexts()
    {
        $log_prefix = getmypid() . ' [' . __METHOD__ . '] ';

        $file_name = '/tmp/contexts';
        if (file_exists($file_name)) {
            $list_context_id = unserialize(file_get_contents($file_name));
            error_log($log_prefix . '(CACHE HIT) $list_context_id : ' . print_r($list_context_id, true));
            return $list_context_id;
        }

        $res = $this->get_contents('https://api.toodledo.com/3/contexts/get.php?access_token=' . $this->$access_token, null, true);
        $contexts = json_decode($res, true);
        $list_context_id = [];
        for ($i = 0; $i < count($contexts); $i++) {
            switch ($contexts[$i]['name']) {
                case '日......':
                    $list_context_id[0] = $contexts[$i]['id'];
                    break;
                case '.月.....':
                    $list_context_id[1] = $contexts[$i]['id'];
                    break;
                case '..火....':
                    $list_context_id[2] = $contexts[$i]['id'];
                    break;
                case '...水...':
                    $list_context_id[3] = $contexts[$i]['id'];
                    break;
                case '....木..':
                    $list_context_id[4] = $contexts[$i]['id'];
                    break;
                case '.....金.':
                    $list_context_id[5] = $contexts[$i]['id'];
                    break;
                case '......土':
                    $list_context_id[6] = $contexts[$i]['id'];
                    break;
            }
        }
        error_log($log_prefix . '$list_context_id : ' . print_r($list_context_id, true));

        file_put_contents($file_name, serialize($list_context_id));

        return $list_context_id;
    }

    function add_tasks($list_add_task_)
    {
        $log_prefix = getmypid() . ' [' . __METHOD__ . '] ';

        error_log($log_prefix . 'ADD TARGET TASK COUNT : ' . count($list_add_task_));

        $list_res = [];

        if (count($list_add_task_) == 0) {
            return $list_res;
        }

        $tmp = array_chunk($list_add_task_, 50);
        for ($i = 0; $i < count($tmp); $i++) {
            $post_data = ['access_token' => $this->$access_token, 'tasks' => '[' . implode(',', $tmp[$i]) . ']'];
            $res = $this->get_contents(
                'https://api.toodledo.com/3/tasks/add.php',
                [CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($post_data),
                ]
            );
            error_log($log_prefix . 'add.php RESPONSE : ' . $res);
            $list_res[] = $res;
        }

        return $list_res;
    }

    function edit_tasks($list_edit_task_)
    {
        $log_prefix = getmypid() . ' [' . __METHOD__ . '] ';

        error_log($log_prefix . 'EDIT TARGET TASK COUNT : ' . count($list_edit_task_));

        $list_res = [];

        if (count($list_edit_task_) == 0) {
            return $list_res;
        }

        $tmp = array_chunk($list_edit_task_, 50);
        for ($i = 0; $i < count($tmp); $i++) {
            $post_data = ['access_token' => $this->$access_token, 'tasks' => '[' . implode(',', $tmp[$i]) . ']'];
            $res = $this->get_contents(
                'https://api.toodledo.com/3/tasks/edit.php',
                [CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($post_data),
                ]
            );
            error_log($log_prefix . 'edit.php RESPONSE : ' . $res);
            $list_res[] = $res;
        }

        return $list_res;
    }

    function delete_tasks($list_delete_task_)
    {
        $log_prefix = getmypid() . ' [' . __METHOD__ . '] ';

        error_log($log_prefix . 'DELETE TARGET TASK COUNT : ' . count($list_delete_task_));

        if (count($list_delete_task_) == 0) {
            return;
        }

        $tmp = array_chunk($list_delete_task_, 50);
        for ($i = 0; $i < count($tmp); $i++) {
            $post_data = ['access_token' => $this->$access_token, 'tasks' => '[' . implode(',', $tmp[$i]) . ']'];
            $res = $this->get_contents(
                'https://api.toodledo.com/3/tasks/delete.php',
                [CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($post_data),
                ]
            );
            error_log($log_prefix . 'delete.php RESPONSE : ' . $res);
        }
    }

    function get_weather_guest_area()
    {
        $log_prefix = getmypid() . ' [' . __METHOD__ . '] ';

        $sql = <<< __HEREDOC__
SELECT T1.location_number
      ,T1.point_name
      ,T1.yyyymmdd
  FROM m_tenki T1;
__HEREDOC__;

        $pdo = $this->get_pdo();
        $list_weather_guest_area = [];
        foreach ($pdo->query($sql) as $row) {
            $location_number = $row['location_number'];
            $point_name = $row['point_name'];
            $yyyymmdd = (int)$row['yyyymmdd'];
            if ($yyyymmdd >= (int)date('Ymd') && $yyyymmdd) {
                $list_weather_guest_area[] = $location_number . ',' . $point_name . ',' . $yyyymmdd;
            }
        }
        error_log($log_prefix . '$list_weather_guest_area : ' . print_r($list_weather_guest_area, true));
        $pdo = null;

        return $list_weather_guest_area;
    }

    function get_env($key_name_)
    {
        $log_prefix = getmypid() . ' [' . __METHOD__ . '] ';

        if (apcu_exists(__METHOD__) === true) {
            $list_env = apcu_fetch(__METHOD__);
            error_log($log_prefix . '(CACHE HIT)$list_env');
        } else {
            $sql = <<< __HEREDOC__
SELECT T1.key
      ,T1.value
  FROM m_env T1
__HEREDOC__;

            $pdo = $this->get_pdo();

            $list_env = [];
            foreach ($pdo->query($sql) as $row) {
                $list_env[$row['key']] = $row['value'];
            }

            error_log($log_prefix . '$list_env : ' . print_r($list_env, true));
            $pdo = null;

            apcu_store(__METHOD__, $list_env);
        }
        $value = '';
        if (array_key_exists($key_name_, $list_env)) {
            $value = $list_env[$key_name_];
        }
        return $value;
    }

    function to_small_size($target_)
    {
        $subscript = '₀₁₂₃₄₅₆₇₈₉';
        for ($i = 0; $i < 10; $i++) {
            $target_ = str_replace($i, mb_substr($subscript, $i, 1), $target_);
        }
        return $target_;
    }

    function get_contents($url_, $options_ = null, $is_cache_search = false)
    {
        $log_prefix = getmypid() . ' [' . __METHOD__ . '] ';

        if ($is_cache_search !== true) {
            return $this->get_contents_nocache($url_, $options_);
        }

        if (is_null($options_) == false && array_key_exists(CURLOPT_POST, $options_) === true) {
            $url_base64 = base64_encode($url_ . '?' . $options_[CURLOPT_POSTFIELDS]);
        } else {
            $url_base64 = base64_encode($url_);
        }

        $sql = <<< __HEREDOC__
SELECT T1.url_base64
      ,T1.content_compress_base64
      ,T1.update_time
      ,CASE WHEN LOCALTIMESTAMP < T1.update_time + interval '22 hours' THEN 0 ELSE 1 END refresh_flag
  FROM t_webcache T1
 WHERE T1.url_base64 = :b_url_base64;
__HEREDOC__;

        $pdo = $this->get_pdo();

        $statement = $pdo->prepare($sql);

        $statement->execute([':b_url_base64' => $url_base64]);
        $result = $statement->fetchAll();

        if (count($result) === 0 || $result[0]['refresh_flag'] == '1') {
            $res = $this->get_contents_nocache($url_, $options_);
            $content_compress_base64 = base64_encode(gzencode($res, 9));

            $sql = <<< __HEREDOC__
DELETE
  FROM t_webcache
 WHERE url_base64 = :b_url_base64
    OR LOCALTIMESTAMP > update_time + interval '5 days';
__HEREDOC__;

            if (count($result) != 0) {
                $statement = $pdo->prepare($sql);
                $rc = $statement->execute([':b_url_base64' => $url_base64]);
                error_log($log_prefix . 'DELETE $rc : ' . $rc);
            }

            $sql = <<< __HEREDOC__
INSERT INTO t_webcache
( url_base64
 ,content_compress_base64
) VALUES (
  :b_url_base64
 ,:b_content_compress_base64
);
__HEREDOC__;
            $statement = $pdo->prepare($sql);
            $rc = $statement->execute([':b_url_base64' => $url_base64,
                                 ':b_content_compress_base64' => $content_compress_base64]);
            error_log($log_prefix . 'INSERT $rc : ' . $rc);
        } else {
            if (is_null($options_) == false && array_key_exists(CURLOPT_POST, $options_) === true) {
                error_log($log_prefix . '(CACHE HIT) url : ' . $url_ . '?' . $options_[CURLOPT_POSTFIELDS]);
            } else {
                error_log($log_prefix . '(CACHE HIT) url : ' . $url_);
            }
            $res = gzdecode(base64_decode($result[0]['content_compress_base64']));
        }
        $pdo = null;
        return $res;
    }

    function get_contents_nocache($url_, $options_ = null)
    {
        $log_prefix = getmypid() . ' [' . __METHOD__ . '] ';
        error_log($log_prefix . 'URL : ' . $url_);
        error_log($log_prefix . 'options : ' . print_r($options_, true));

        $options = [
        CURLOPT_URL => $url_,
        CURLOPT_USERAGENT => getenv('USER_AGENT'),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_FOLLOWLOCATION => 1,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_SSL_FALSESTART => true,
        ];

        $time_start = 0;
        $time_finish = 0;
        for ($i = 0; $i < 3; $i++) {
            $time_start = microtime(true);
            $ch = curl_init();
            curl_setopt_array($ch, $options);
            if (is_null($options_) == false) {
                curl_setopt_array($ch, $options_);
            }
            $res = curl_exec($ch);
            $time_finish = microtime(true);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            error_log($log_prefix . "HTTP STATUS CODE : ${http_code} [" . substr(($time_finish - $time_start), 0, 5) . 'sec]');
            curl_close($ch);
            if ($http_code == '200') {
                break;
            }

            error_log($log_prefix . '$res : ' . $res);

            if ($http_code != '503') {
                break;
            } else {
                sleep(3);
                error_log($log_prefix . 'RETRY URL : ' . $url_);
            }
        }

        error_log($log_prefix . 'LENGTH : ' . strlen($res));
        return $res;
    }

    function get_contents_multi($urls_, $urls_is_cache_ = null)
    {
        $log_prefix = getmypid() . ' [' . __METHOD__ . '] ';

        if (is_null($urls_)) {
            $urls_ = [];
        }
        if (is_null($urls_is_cache_)) {
            $urls_is_cache_ = [];
        }
        
        $sql_select = <<< __HEREDOC__
SELECT T1.url_base64
      ,T1.content_compress_base64
  FROM t_webcache T1
 WHERE CASE WHEN LOCALTIMESTAMP < T1.update_time + interval '1 days' THEN 0 ELSE 1 END = 0
__HEREDOC__;

        $pdo = $this->get_pdo();
        $statement = $pdo->prepare($sql_select);
        $statement->execute();
        $results = $statement->fetchAll();

        foreach ($results as $result) {
            $cache_data[$result['url_base64']] = $result['content_compress_base64'];
        }

        $results_cache = [];

        foreach ($urls_is_cache_ as $url => $options) {
            if (array_key_exists(base64_encode($url), $cache_data)) {
                error_log($log_prefix . '(CACHE HIT) $url : ' . $url);
                $results_cache[$url] = gzdecode(base64_decode($cache_data[base64_encode($url)]));
            } else {
                $urls_[$url] = $options;
            }
        }

        $mh = curl_multi_init();

        foreach ($urls_ as $url => $options_add) {
            error_log($log_prefix . 'CURL MULTI Add $url : ' . $url);
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
                error_log($log_prefix . 'CURL Result $url : ' . $url);
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
            error_log($log_prefix . 'DELETE $rc : ' . $rc);

            $statement = $pdo->prepare($sql_insert);
            $rc = $statement->execute([':b_url_base64' => $url_base64,
                                       ':b_content_compress_base64' => base64_encode(gzencode($result, 9))]);
            error_log($log_prefix . 'INSERT $rc : ' . $rc);
        }

        $pdo = null;

        $results = array_merge($results, $results_cache);

        error_log($log_prefix . 'urls : ' . print_r(array_keys($results), true));

        return $results;
    }
}
