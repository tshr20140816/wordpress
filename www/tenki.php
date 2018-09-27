<?php

// $res = file_get_contents('https://rss-weather.yahoo.co.jp/rss/days/' . getenv('LOCATION_NUMBER') . '.xml');
$rss = simplexml_load_file('https://rss-weather.yahoo.co.jp/rss/days/' . getenv('LOCATION_NUMBER') . '.xml');

$counter = 0;
foreach($rss->channel->item as $item) {

  if ($counter++ == 7) {
    break;
  }
  echo $item->title;
  echo $item->link;
  
}

?>
