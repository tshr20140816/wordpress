<?php

include(dirname(__FILE__) . '/../classes/MyUtils.php');

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s'));

$mu = new MyUtils();

$timeout = 10;

$mh = curl_multi_init();

$urls = [$mu->get_env('URL_KASA_SHISU_YAHOO'), $mu->get_env('URL_WEATHER_WARN')];

error_log(print_r($urls, true));

foreach ($urls as $u) {
    error_log('POINT 100');
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL            => $u,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_CONNECTTIMEOUT => $timeout,
        CURLOPT_USERAGENT => getenv('USER_AGENT'),
    ));
    curl_multi_add_handle($mh, $ch);
    error_log('POINT 110');
}

do {
    error_log('POINT 120');
    $stat = curl_multi_exec($mh, $running); //multiリクエストスタート
    error_log('POINT 130');
} while ($stat === CURLM_CALL_MULTI_PERFORM);
if ( ! $running || $stat !== CURLM_OK) {
    // throw new RuntimeException('リクエストが開始出来なかった。マルチリクエスト内のどれか、URLの設定がおかしいのでは？');
    error_log('POINT 140');
    exit();
}

error_log('POINT 150');

do switch (curl_multi_select($mh, $timeout)) { //イベントが発生するまでブロック
    // 最悪$TIMEOUT秒待ち続ける。
    // あえて早めにtimeoutさせると、レスポンスを待った状態のまま別の処理を挟めるようになります。
    // もう一度curl_multi_selectを繰り返すと、またイベントがあるまでブロックして待ちます。

    case -1: //selectに失敗。ありうるらしい。 https://bugs.php.net/bug.php?id=61141
        error_log('POINT 160');
        usleep(10); //ちょっと待ってからretry。ここも別の処理を挟んでもよい。
        do {
            $stat = curl_multi_exec($mh, $running);
        } while ($stat === CURLM_CALL_MULTI_PERFORM);
        error_log('POINT 170');
        continue 2;

    case 0:  //タイムアウト -> 必要に応じてエラー処理に入るべきかも。
        error_log('POINT 180');
        break;
        // continue 2; //ここではcontinueでリトライします。

    default: //どれかが成功 or 失敗した
        error_log('POINT 190');
        do {
            $stat = curl_multi_exec($mh, $running); //ステータスを更新
        } while ($stat === CURLM_CALL_MULTI_PERFORM);
        error_log('POINT 200');

        do if ($raised = curl_multi_info_read($mh, $remains)) {
            //変化のあったcurlハンドラを取得する
            $info = curl_getinfo($raised['handle']);
            // echo "$info[url]: $info[http_code]\n";
            $response = curl_multi_getcontent($raised['handle']);

            if ($response === false) {
                //エラー。404などが返ってきている
                // echo 'ERROR!!!', PHP_EOL;
                error_log('POINT 300');
            } else {
                //正常にレスポンス取得
                //echo $response, PHP_EOL;
                error_log('POINT 310');
            }
            curl_multi_remove_handle($mh, $raised['handle']);
            curl_close($raised['handle']);
        } while ($remains);
        error_log('POINT 400');
        //select前に全ての処理が終わっていたりすると
        //複数の結果が入っていることがあるのでループが必要

} while ($running);

curl_multi_close($mh);

error_log(getmypid() . ' FINISH');
