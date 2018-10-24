<?php

$res = file_get_contents('https://eco.mtk.nao.ac.jp/koyomi/dni/2018/s1308.html');

$tmp = explode('<table ', $res);
$tmp = explode('</table>', $tmp[1]);
$tmp = explode('</tr>', $tmp[0]);
array_shift($tmp);
array_pop($tmp);

error_log(print_r($tmp, TRUE));

// <tr><td id="m0801"> 1</td><td> 4:49</td> <td> 66.8</td> <td>11:47</td> <td> 72.4</td> <td>18:46</td> <td>293.0</td>

$rc = preg_match('/.+?<\/td><td>(.+?)<\/td>.*?<td>.+?<\/td>.*?<td>.+?<\/td>.*?<td>.+?<\/td>.*?<td>(.+?)</', $tmp[0], $matches);

error_log(print_r($matches, TRUE));

$rc = preg_match('/.+?<\/td><td>(.+?)<\/td>.*?<td>.+?<\/td>.*?<td>.+?<\/td>.*?<td>.+?<\/td>.*?<td>(.+?)</', $tmp[1], $matches);

error_log(print_r($matches, TRUE));
?>
