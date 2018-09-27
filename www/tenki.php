<?php

// $res = file_get_contents('https://rss-weather.yahoo.co.jp/rss/days/' . getenv('LOCATION_NUMBER') . '.xml');
$rss = simplexml_load_file('https://rss-weather.yahoo.co.jp/rss/days/' . getenv('LOCATION_NUMBER') . '.xml');

$counter = 0;
foreach($rss->channel->item as $item) {

  if ($counter++ == 7) {
    break;
  }
  
  $url = $item->link;
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
  curl_exec($ch);
  $info = curl_getinfo($ch);
  echo $info['CURLINFO_EFFECTIVE_URL'];
  curl_close($ch);
  
  echo $item->title;
  echo $item->link;
  
}

?>
