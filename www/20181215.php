<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s'));

$mu = new MyUtils();

for ($i = 1; $i < 5; $i++) {
  $url = 'http://www.motomachi-pa.jp/cgi/manku.pl?park_id=' . $i . '&mode=pc';
  $res = $mu->get_contents($url);

  $hash_text = hash('sha512', $res);

  $pdo = $mu->get_pdo();

  $sql = <<< __HEREDOC__
SELECT T1.parse_text
  FROM t_imageparsehash T1
 WHERE T1.group_id = 2
   AND T1.hash_text = :b_hash_text;
__HEREDOC__;

  $statement = $pdo->prepare($sql);
  $rc = $statement->execute([':b_hash_text' => $hash_text]);
  error_log("${pid} SELECT RESULT : ${rc}");
  $results = $statement->fetchAll();
  error_log($pid . ' $results : ' . print_r($results, TRUE));

  $parse_text = '';
  foreach ($results as $row) {
    $parse_text = $row['parse_text'];
  }

  $pdo = NULL;

  if (strlen($parse_text) > 0) {
    file_put_contents('/tmp/outlet_parking_information.txt', $parse_text);
    error_log("${pid} (CACHE HIT)PARSE TEXT ${parse_text}");
    continue;
  }

  $im1 = imagecreatefromstring($res);
  imagefilter($im1, IMG_FILTER_NEGATE);
  $file = '/tmp/motomachi_parking_information.png';
  imagepng($im1, $file);
  imagedestroy($im1);

  $url = 'https://api.cloudmersive.com/ocr/image/toText';

  $post_data = ['imageFile' => new CURLFile($file)];

  $options = [
    CURLOPT_POST => TRUE,
    CURLOPT_HTTPHEADER => ['Apikey: ' . getenv('CLOUDMERSIVE_API_KEY'),
                           'language: JPN',
                           'Accept: application/json'],
    CURLOPT_POSTFIELDS => $post_data,
    CURLOPT_TIMEOUT => 20,
    ];

  $res = $mu->get_contents($url, $options);

  $data = json_decode($res);
  error_log($pid . ' $data : ' . print_r($data, TRUE));

  $parse_text = trim($data->TextResult);
  file_put_contents('/tmp/motomachi_parking_information.txt', $parse_text);
  error_log("${pid} PARSE TEXT ${parse_text}");

  if (strlen($parse_text) > 0) {
    $pdo = $mu->get_pdo();

    $sql = <<< __HEREDOC__
INSERT INTO t_imageparsehash
(
 group_id
,hash_text
,parse_text
) VALUES (
 2
,:b_hash_text
,:b_parse_text
);
__HEREDOC__;

    $statement = $pdo->prepare($sql);
    $rc = $statement->execute([':b_hash_text' => $hash_text,
                               ':b_parse_text' => $parse_text]);
  
    error_log("${pid} INSERT RESULT : ${rc}");
  
    $pdo = NULL;
  }
  break;
}
error_log("${pid} FINISH");
?>
