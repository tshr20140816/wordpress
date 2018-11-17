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
        error_log(getmypid() . ' (CACHE HIT) $access_token : ' . $access_token);
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
      error_log(getmypid() . ' ACCESS TOKEN NONE');
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
      error_log(getmypid() . " refresh_token : ${refresh_token}");
      $post_data = ['grant_type' => 'refresh_token', 'refresh_token' => $refresh_token];

      $res = $this->get_contents(
        'https://api.toodledo.com/3/account/token.php',
        [CURLOPT_USERPWD => getenv('TOODLEDO_CLIENTID') . ':' . getenv('TOODLEDO_SECRET'),
         CURLOPT_POST => TRUE,
         CURLOPT_POSTFIELDS => http_build_query($post_data),
        ]);

      error_log(getmypid() . " token.php RESPONSE : ${res}");
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
      error_log(getmypid() . " UPDATE RESULT : ${rc}");
  
      $access_token = $params['access_token'];
    }
    $pdo = null;
    
    error_log(getmypid() . ' $access_token : ' . $access_token);
    
    $this->$_access_token = $access_token;

    file_put_contents($file_name, $access_token); // For Cache
    
    return $access_token;
  }
  
  function get_folder_id($folder_name_) {
    
    $file_name = '/tmp/folders';
    if (file_exists($file_name)) {
      $folders = unserialize(file_get_contents($file_name));
      error_log(getmypid() . ' (CACHE HIT) FOLDERS');
    } else {
      $res = $this->get_contents('https://api.toodledo.com/3/folders/get.php?access_token=' . $this->$access_token);
      $folders = json_decode($res, TRUE);
      file_put_contents($file_name, serialize($folders));
    }

    $target_folder_id = 0;
    for ($i = 0; $i < count($folders); $i++) {
      if ($folders[$i]['name'] == $folder_name_) {
        $target_folder_id = $folders[$i]['id'];
        error_log(getmypid() . " ${folder_name_} FOLDER ID : ${target_folder_id}");
        break;
      }
    }
    return $target_folder_id;
  }
  
  function get_contexts() {
    
    $file_name = '/tmp/contexts';
    if (file_exists($file_name)) {
      $list_context_id = unserialize(file_get_contents($file_name));
      error_log(getmypid() . ' (CACHE HIT) $list_context_id : ' . print_r($list_context_id, TRUE));
      return $list_context_id;
    }
    
    $res = $this->get_contents('https://api.toodledo.com/3/contexts/get.php?access_token=' . $this->$access_token);
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
    error_log(getmypid() . ' $list_context_id : ' . print_r($list_context_id, TRUE));
    
    file_put_contents($file_name, serialize($list_context_id));
    
    return $list_context_id;
  }
  
  function add_tasks($list_add_task_) {
    
    error_log(getmypid() . ' ADD TARGET TASK COUNT : ' . count($list_add_task_));
    
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
      error_log(getmypid() . ' add.php RESPONSE : ' . $res);
      $list_res[] = $res;
    }
    
    return $list_res;
  }
  
  function delete_tasks($list_delete_task_) {
    
    error_log(getmypid() . ' DELETE TARGET TASK COUNT : ' . count($list_delete_task_));
    
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
      error_log(getmypid() . ' delete.php RESPONSE : ' . $res);
    }
  }
  
  function get_weather_guest_area() {

    $sql = <<< __HEREDOC__
SELECT T1.location_number
      ,T1.point_name
      ,T1.yyyymmdd
  FROM m_tenki T1
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
    error_log(getmypid() . ' $list_weather_guest_area : ' . print_r($list_weather_guest_area, TRUE));
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
  
  function get_contents($url_, $options_ = NULL) {
    error_log(getmypid() . ' URL : ' . $url_);
    
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
      error_log(getmypid() . ' HTTP STATUS CODE : ' . $http_code);
      curl_close($ch);
      if ($http_code == '200') {
        break;
      }
      
      error_log(getmypid() . ' $res : ' . $res);
      
      if ($http_code != '503') {
        break;
      } else {
        sleep(3);
        error_log(getmypid() . ' RETRY URL : ' . $url_);
      }
    }

    return $res;
  }
}
?>
