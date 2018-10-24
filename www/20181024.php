<?php

$yyyy = 2019;

$post_data = ['from_year' => $yyyy];

$res = get_contents(
  'http://www.calc-site.com/calendars/solar_year',
  [CURLOPT_POST => TRUE,
   CURLOPT_POSTFIELDS => http_build_query($post_data),
  ]);

// error_log($res);

$tmp = explode('<th>二十四節気</th>', $res);
$tmp = explode('</table>', $tmp[1]);

// error_log($tmp[0]);

$tmp = explode('<tr>', $tmp[0]);
array_shift($tmp);

error_log(print_r($tmp, TRUE));

for ($i = 0; $i < count($tmp); $i++) {
  $rc = preg_match('/<td>(.+?)<.+?<.+?>(.+?)</', $tmp[$i], $matches);
  // error_log(print_r($matches, TRUE));
  // $yyyy
  $tmp1 = $matches[2];
  $tmp1 = str_replace('月', '-', $tmp1);
  $tmp1 = str_replace('日', '', $tmp1);
  $tmp1 = $yyyy . '-' . $tmp1;
  error_log(date('Ymd', strtotime($tmp1)) . ' ' . $matches[1]);
}

function get_contents($url_, $options_) {
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
?>
