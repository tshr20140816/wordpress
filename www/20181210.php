<?php

$res = get_contents('https://eco.mtk.nao.ac.jp/koyomi/dni/2018/s3512.html', NULL, TRUE);

// error_log($res);

exit();

function get_pdo() {
  $connection_info = parse_url(getenv('DATABASE_URL'));
  return new PDO(
    "pgsql:host=${connection_info['host']};dbname=" . substr($connection_info['path'], 1),
    $connection_info['user'],
    $connection_info['pass']);
}

function get_contents($url_, $options_ = NULL, $is_cache_search = FALSE) {
  
  if ($is_cache_search !== TRUE) {
    return $this->get_contents_nocache($url_, $options_);
  }
  
  $url_base64 = base64_encode($url_);

  $sql = <<< __HEREDOC__
SELECT T1.url_base64
      ,T1.content_compress_base64
      ,T1.update_time
      ,CASE WHEN LOCALTIMESTAMP < T1.update_time + interval '1 days' THEN 0 ELSE 1 END refresh_flag
  FROM t_webcache T1
 WHERE T1.url_base64 = :b_url_base64;
__HEREDOC__;
  
  $pdo = get_pdo();
  
  $statement = $pdo->prepare($sql);
  
  $statement->execute([':b_url_base64' => $url_base64]);
  $result = $statement->fetchAll();
  
  error_log(getmypid() . ' $result : ' . print_r($result, TRUE));
  error_log(getmypid() . ' errorInfo : ' . print_r($pdo->errorInfo(), TRUE));
  
  if (count($result) === 0 || $result[0]['refresh_flag'] == '1') {
    $res = get_contents_nocache($url_, $options_);
    $content_compress_base64 = base64_encode(gzencode($res, 9));
    
    $sql = <<< __HEREDOC__
DELETE
  FROM t_webcache
 WHERE url_base64 = :b_url_base64
    OR LOCALTIMESTAMP > update_time + interval '5 days';
__HEREDOC__;
    
    if (count($result) != 0) {
      error_log(getmypid() . ' $sql : ' . $sql);
      error_log(getmypid() . ' $url_base64 : ' . $url_base64);
      $statement = $pdo->prepare($sql);
      error_log(getmypid() . ' prepare errorInfo : ' . print_r($pdo->errorInfo(), TRUE));
      $rc = $statement->execute([':b_url_base64' => $url_base64]);
      error_log(getmypid() . ' execute errorInfo : ' . print_r($pdo->errorInfo(), TRUE));
      error_log(getmypid() . ' DELETE $rc : ' . $rc);
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
    error_log(getmypid() . ' $sql : ' . $sql);
    error_log(getmypid() . ' $url_base64 : ' . $url_base64);
    $statement = $pdo->prepare($sql);
    error_log(getmypid() . ' prepare errorInfo : ' . print_r($pdo->errorInfo(), TRUE));
    $rc = $statement->execute([':b_url_base64' => $url_base64,
                               ':b_content_compress_base64' => $content_compress_base64]);
    error_log(getmypid() . ' execute errorInfo : ' . print_r($pdo->errorInfo(), TRUE));
    error_log(getmypid() . ' INSERT $rc : ' . $rc);
  } else {
    error_log(getmypid() . ' (CACHE HIT) url : ' . $url_);
    $res = gzdecode(base64_decode($result[0]['content_compress_base64']));
  }
  $pdo = NULL;
  return $res;
}

function get_contents_nocache($url_, $options_ = NULL) {
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

?>
