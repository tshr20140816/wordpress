<?php

class MyUtils
{
  private $_access_token;

  function get_pdo() {
    $connection_info = parse_url(getenv('DATABASE_URL'));
    return new PDO(
      "pgsql:host=${connection_info['host']};dbname=" . substr($connection_info['path'], 1),
      $connection_info['user'],
      $connection_info['pass']);
  }

  function get_access_token() {

    $file_name = '/tmp/access_token';

    if (file_exists($file_name)) {
      $timestamp = filemtime($file_name);
      if ($timestamp > strtotime('-15 minutes')) {
        $access_token = file_get_contents($file_name);
        error_log(getmypid() . '[' . __METHOD__ . '](CACHE HIT) $access_token : ' . $access_token);
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

    $access_token = NULL;
    foreach ($pdo->query($sql) as $row) {
      $access_token = $row['access_token'];
      $refresh_token = $row['refresh_token'];
      $refresh_flag = $row['refresh_flag'];
    }

    if ($access_token == NULL) {
      error_log(getmypid() . '[' . __METHOD__ . ']ACCESS TOKEN NONE');
      exit();
    }

    if ($refresh_flag == 0) {
      $res = $this->get_contents('https://api.toodledo.com/3/folders/get.php?access_token=' . $access_token);
      if ($res == '{"errorCode":2,"errorDesc":"Unauthorized","errors":[{"status":"2","message":"Unauthorized"}]}') {
        $refresh_flag = 1;
      } else {
        file_put_contents('/tmp/folders', serialize(json_decode($res, TRUE)));
      }
    }

    if ($refresh_flag == 1) {
      error_log(getmypid() . '[' . __METHOD__ . "]refresh_token : ${refresh_token}");
      $post_data = ['grant_type' => 'refresh_token', 'refresh_token' => $refresh_token];

      $res = $this->get_contents(
        'https://api.toodledo.com/3/account/token.php',
        [CURLOPT_USERPWD => getenv('TOODLEDO_CLIENTID') . ':' . getenv('TOODLEDO_SECRET'),
         CURLOPT_POST => TRUE,
         CURLOPT_POSTFIELDS => http_build_query($post_data),
        ]);

      error_log(getmypid() . '[' . __METHOD__ . "]token.php RESPONSE : ${res}");
      $params = json_decode($res, TRUE);

      $sql = <<< __HEREDOC__
UPDATE m_authorization
   SET access_token = :b_access_token
      ,refresh_token = :b_refresh_token
      ,update_time = LOCALTIMESTAMP;
__HEREDOC__;

      $statement = $pdo->prepare($sql);
      $rc = $statement->execute([':b_access_token' => $params['access_token'],
                                 ':b_refresh_token' => $params['refresh_token']]);
      error_log(getmypid() . '[' . __METHOD__ . "]UPDATE RESULT : ${rc}");

      $access_token = $params['access_token'];
    }
    $pdo = null;

    error_log(getmypid() . '[' . __METHOD__ . ']$access_token : ' . $access_token);

    $this->$_access_token = $access_token;

    file_put_contents($file_name, $access_token); // For Cache

    return $access_token;
  }

  function get_folder_id($folder_name_) {

    $file_name = '/tmp/folders';
    if (file_exists($file_name)) {
      $folders = unserialize(file_get_contents($file_name));
      error_log(getmypid() . '[' . __METHOD__ . '](CACHE HIT) FOLDERS');
    } else {
      $res = $this->get_contents('https://api.toodledo.com/3/folders/get.php?access_token=' . $this->$access_token, NULL, TRUE);
      $folders = json_decode($res, TRUE);
      file_put_contents($file_name, serialize($folders));
    }

    $target_folder_id = 0;
    for ($i = 0; $i < count($folders); $i++) {
      if ($folders[$i]['name'] == $folder_name_) {
        $target_folder_id = $folders[$i]['id'];
        error_log(getmypid() . '[' . __METHOD__ . "]${folder_name_} FOLDER ID : ${target_folder_id}");
        break;
      }
    }
    return $target_folder_id;
  }

  function get_contexts() {

    $file_name = '/tmp/contexts';
    if (file_exists($file_name)) {
      $list_context_id = unserialize(file_get_contents($file_name));
      error_log(getmypid() . '[' . __METHOD__ . '](CACHE HIT) $list_context_id : ' . print_r($list_context_id, TRUE));
      return $list_context_id;
    }

    $res = $this->get_contents('https://api.toodledo.com/3/contexts/get.php?access_token=' . $this->$access_token, NULL, TRUE);
    $contexts = json_decode($res, TRUE);
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
    error_log(getmypid() . '[' . __METHOD__ . ']$list_context_id : ' . print_r($list_context_id, TRUE));

    file_put_contents($file_name, serialize($list_context_id));

    return $list_context_id;
  }

  function add_tasks($list_add_task_) {

    error_log(getmypid() . '[' . __METHOD__ . ']ADD TARGET TASK COUNT : ' . count($list_add_task_));

    $list_res = [];

    if (count($list_add_task_) == 0) {
      return $list_res;
    }

    $tmp = array_chunk($list_add_task_, 50);
    for ($i = 0; $i < count($tmp); $i++) {
      $post_data = ['access_token' => $this->$access_token, 'tasks' => '[' . implode(',', $tmp[$i]) . ']'];
      $res = $this->get_contents(
        'https://api.toodledo.com/3/tasks/add.php',
        [CURLOPT_POST => TRUE,
         CURLOPT_POSTFIELDS => http_build_query($post_data),
        ]);
      error_log(getmypid() . '[' . __METHOD__ . ']add.php RESPONSE : ' . $res);
      $list_res[] = $res;
    }

    return $list_res;
  }

  function edit_tasks($list_edit_task_) {

    error_log(getmypid() . '[' . __METHOD__ . ']EDIT TARGET TASK COUNT : ' . count($list_edit_task_));

    $list_res = [];

    if (count($list_edit_task_) == 0) {
      return $list_res;
    }

    $tmp = array_chunk($list_edit_task_, 50);
    for ($i = 0; $i < count($tmp); $i++) {
      $post_data = ['access_token' => $this->$access_token, 'tasks' => '[' . implode(',', $tmp[$i]) . ']'];
      $res = $this->get_contents(
        'https://api.toodledo.com/3/tasks/edit.php',
        [CURLOPT_POST => TRUE,
         CURLOPT_POSTFIELDS => http_build_query($post_data),
        ]);
      error_log(getmypid() . '[' . __METHOD__ . ']edit.php RESPONSE : ' . $res);
      $list_res[] = $res;
    }

    return $list_res;
  }

  function delete_tasks($list_delete_task_) {

    error_log(getmypid() . '[' . __METHOD__ . ']DELETE TARGET TASK COUNT : ' . count($list_delete_task_));

    if (count($list_delete_task_) == 0) {
      return;
    }

    $tmp = array_chunk($list_delete_task_, 50);
    for ($i = 0; $i < count($tmp); $i++) {
      $post_data = ['access_token' => $this->$access_token, 'tasks' => '[' . implode(',', $tmp[$i]) . ']'];
      $res = $this->get_contents(
        'https://api.toodledo.com/3/tasks/delete.php',
        [CURLOPT_POST => TRUE,
         CURLOPT_POSTFIELDS => http_build_query($post_data),
        ]);
      error_log(getmypid() . '[' . __METHOD__ . ']delete.php RESPONSE : ' . $res);
    }
  }

  function get_weather_guest_area() {

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
    error_log(getmypid() . '[' . __METHOD__ . ']$list_weather_guest_area : ' . print_r($list_weather_guest_area, TRUE));
    $pdo = null;

    return $list_weather_guest_area;
  }

  function to_small_size($target_) {
    $subscript = '₀₁₂₃₄₅₆₇₈₉';
    for ($i = 0; $i < 10; $i++) {
      $target_ = str_replace($i, mb_substr($subscript, $i, 1), $target_);
    }
    return $target_;
  }

  function get_contents($url_, $options_ = NULL, $is_cache_search = FALSE) {

    if ($is_cache_search !== TRUE) {
      return $this->get_contents_nocache($url_, $options_);
    }

    if (is_null($options_) == FALSE && array_key_exists(CURLOPT_POST, $options_) === TRUE) {
      $url_base64 = base64_encode($url_ . '?' . $options_[CURLOPT_POSTFIELDS]);
    } else {
      $url_base64 = base64_encode($url_);
    }

    $sql = <<< __HEREDOC__
SELECT T1.url_base64
      ,T1.content_compress_base64
      ,T1.update_time
      ,CASE WHEN LOCALTIMESTAMP < T1.update_time + interval '1 days' THEN 0 ELSE 1 END refresh_flag
  FROM t_webcache T1
 WHERE T1.url_base64 = :b_url_base64;
__HEREDOC__;

    $pdo = $this->get_pdo();

    $statement = $pdo->prepare($sql);

    $statement->execute([':b_url_base64' => $url_base64]);
    $result = $statement->fetchAll();

    // error_log(getmypid() . ' $result : ' . print_r($result, TRUE));
    // error_log(getmypid() . ' errorInfo : ' . print_r($pdo->errorInfo(), TRUE));

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
        // error_log(getmypid() . ' prepare errorInfo : ' . print_r($pdo->errorInfo(), TRUE));
        $rc = $statement->execute([':b_url_base64' => $url_base64]);
        error_log(getmypid() . '[' . __METHOD__ . ']DELETE $rc : ' . $rc);
        // error_log(getmypid() . ' execute errorInfo : ' . print_r($pdo->errorInfo(), TRUE));
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
      // error_log(getmypid() . ' prepare errorInfo : ' . print_r($pdo->errorInfo(), TRUE));
      $rc = $statement->execute([':b_url_base64' => $url_base64,
                                 ':b_content_compress_base64' => $content_compress_base64]);
      error_log(getmypid() . '[' . __METHOD__ . ']INSERT $rc : ' . $rc);
      // error_log(getmypid() . ' execute errorInfo : ' . print_r($pdo->errorInfo(), TRUE));
    } else {
      if (is_null($options_) == FALSE && array_key_exists(CURLOPT_POST, $options_) === TRUE) {
        error_log(getmypid() . '[' . __METHOD__ . '](CACHE HIT) url : ' . $url_ . '?' . $options_[CURLOPT_POSTFIELDS]);
      } else {
        error_log(getmypid() . '[' . __METHOD__ . '](CACHE HIT) url : ' . $url_);
      }
      $res = gzdecode(base64_decode($result[0]['content_compress_base64']));
    }
    $pdo = NULL;
    return $res;
  }

  function get_contents_nocache($url_, $options_ = NULL) {
    error_log(getmypid() . '[' . __METHOD__ . ']URL : ' . $url_);
    error_log(getmypid() . '[' . __METHOD__ . ']options : ' . print_r($options_, TRUE));

    $options = [
      CURLOPT_URL => $url_,
      CURLOPT_USERAGENT => getenv('USER_AGENT'),
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_ENCODING => '',
      CURLOPT_FOLLOWLOCATION => 1,
      CURLOPT_MAXREDIRS => 3,
      CURLOPT_SSL_FALSESTART => TRUE,
    ];

    for ($i = 0; $i < 3; $i++) {
      $ch = curl_init();
      curl_setopt_array($ch, $options);
      if (is_null($options_) == FALSE) {
        curl_setopt_array($ch, $options_);
      }
      $res = curl_exec($ch);
      $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      error_log(getmypid() . '[' . __METHOD__ . ']HTTP STATUS CODE : ' . $http_code);
      curl_close($ch);
      if ($http_code == '200') {
        break;
      }

      error_log(getmypid() . '[' . __METHOD__ . ']$res : ' . $res);

      if ($http_code != '503') {
        break;
      } else {
        sleep(3);
        error_log(getmypid() . '[' . __METHOD__ . ']RETRY URL : ' . $url_);
      }
    }

    error_log(getmypid() . '[' . __METHOD__ . ']LENGTH : ' . strlen($res));
    return $res;
  }
}
?>
