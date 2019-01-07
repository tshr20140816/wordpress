<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
$time_start = time();
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s', $time_start));

$rc = apcu_clear_cache();

$mu = new MyUtils();

//

$sub_address = $mu->get_env('SUB_ADDRESS');

for ($i = 11; $i > -1; $i--) {
    $url = 'https://feed43.com/' . $sub_address . ($i * 5 + 11) . '-' . ($i * 5 + 15) . '.xml';
    $res = $mu->get_contents($url);
}

//

for ($j = 0; $j < 4; $j++) {
    $yyyy = date('Y', strtotime('+' . $j . ' years'));

    $url = 'http://calendar-service.net/cal?start_year=' . $yyyy
        . '&start_mon=1&end_year=' . $yyyy . '&end_mon=12'
        . '&year_style=normal&month_style=numeric&wday_style=ja_full&format=csv&holiday_only=1&zero_padding=1';

    $res = $mu_->get_contents($url, null, true);
}

//

$start_yyyy = date('Y');
$start_m = date('n');
$finish_yyyy = date('Y', strtotime('+3 month'));
$finish_m = date('n', strtotime('+3 month'));

$url = 'http://calendar-service.net/cal?start_year=' . $start_yyyy
    . '&start_mon=' . $start_m . '&end_year=' . $finish_yyyy . '&end_mon=' . $finish_m
    . '&year_style=normal&month_style=numeric&wday_style=ja_full&format=csv&holiday_only=1&zero_padding=1';

$res = $mu_->get_contents($url, null, true);

//

$yyyy = (int)date('Y');
for ($j = 0; $j < 2; $j++) {
    $post_data = ['from_year' => $yyyy];

    $res = $mu_->get_contents(
        'http://www.calc-site.com/calendars/solar_year',
        [CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($post_data),
        ],
        true
    );

    $yyyy++;
}

//

$area_id = $mu_->get_env('AREA_ID');
for ($j = 0; $j < 4; $j++) {
    $timestamp = strtotime(date('Y-m-01') . " +${j} month");
    $yyyy = date('Y', $timestamp);
    $mm = date('m', $timestamp);
    $res = $mu_->get_contents('https://eco.mtk.nao.ac.jp/koyomi/dni/' . $yyyy . '/s' . $area_id . $mm . '.html', null, true);
}

//

$timestamp = strtotime('+1 day');
$yyyy = date('Y', $timestamp);
$mm = date('m', $timestamp);

$res = $mu_->get_contents('https://eco.mtk.nao.ac.jp/koyomi/dni/' . $yyyy . '/s' . $mu_->get_env('AREA_ID') . $mm . '.html', null, true);

//

$timestamp = strtotime('+1 day');
$yyyy = date('Y', $timestamp);
$mm = date('m', $timestamp);

$res = $mu_->get_contents('https://eco.mtk.nao.ac.jp/koyomi/dni/' . $yyyy . '/m' . $mu_->get_env('AREA_ID') . $mm . '.html', null, true);

$time_finish = time();
error_log("${pid} FINISH " . date('s', $time_finish - $time_start) . 's');
exit();
