<?php

class MyUtils
{
  private $_pdo;
  private $_access_token;
  
  function __construct() {    
    $connection_info = parse_url(getenv('DATABASE_URL'));
    $this->$_pdo = new PDO(
      "pgsql:host=${connection_info['host']};dbname=" . substr($connection_info['path'], 1),
      $connection_info['user'],
      $connection_info['pass']);
  }
  
  function __destruct() {
    $this->$_pdo = NULL;
  }
  
  function get_access_token() {
    
    $sql = <<< __HEREDOC__
SELECT M1.access_token
      ,M1.refresh_token
      ,M1.expires_in
      ,M1.create_time
      ,M1.update_time
      ,CASE WHEN LOCALTIMESTAMP < M1.update_time + interval '90 minutes' THEN 0 ELSE 1 END refresh_flag
  FROM m_authorization M1;
__HEREDOC__;
    
    $access_token = NULL;
    foreach ($this->$_pdo->query($sql) as $row) {
      $access_token = $row['access_token'];
      $refresh_token = $row['refresh_token'];
      $refresh_flag = $row['refresh_flag'];
    }
    
    if ($access_token == NULL) {
      error_log(getmypid() . ' ACCESS TOKEN NONE');
      exit();
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
  
      $statement = $this->$_pdo->prepare($sql);
      $rc = $statement->execute([':b_access_token' => $params['access_token'],
                                 ':b_refresh_token' => $params['refresh_token']]);
      error_log(getmypid() . " UPDATE RESULT : ${rc}");
  
      $access_token = $params['access_token'];
    }
    
    error_log(getmypid() . ' $access_token : ' . $access_token);
    
    $this->$_access_token = $access_token;
    
    return $access_token;
  }
  
  function get_folder_id($folder_name_) {
    $file_name = '/tmp/' . $folder_name_;
    if (file_exists($file_name)) {
      error_log(getmypid() . " (CACHE HIT) ${folder_name_} FOLDER ID : ${target_folder_id}");
      return file_get_contents($file_name);
    }
    $res = $this->get_contents('https://api.toodledo.com/3/folders/get.php?access_token=' . $this->$access_token);
    $folders = json_decode($res, TRUE);

    $target_folder_id = 0;
    for ($i = 0; $i < count($folders); $i++) {
      if ($folders[$i]['name'] == $folder_name_) {
        $target_folder_id = $folders[$i]['id'];
        error_log(getmypid() . " ${folder_name_} FOLDER ID : ${target_folder_id}");
        break;
      }
    }
    file_put_contents($file_name, $target_folder_id);
    return $target_folder_id;
  }
  
  function get_contents($url_, $options_ = NULL) {
    $ch = curl_init();
    curl_setopt_array($ch, [
      CURLOPT_URL => $url_,
      CURLOPT_USERAGENT => getenv('USER_AGENT'),
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_ENCODING => '',
      CURLOPT_FOLLOWLOCATION => 1,
      CURLOPT_MAXREDIRS => 3,
      CURLOPT_SSL_FALSESTART => TRUE,
      ]);
    if (is_null($options_) == FALSE) {
      curl_setopt_array($ch, $options_);
    }
    $res = curl_exec($ch);
    curl_close($ch);
  
    return $res;
  }
}
?>
