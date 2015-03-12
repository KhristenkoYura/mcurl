<?php
/**
 * facebook api: get website comments and shares
 * Result:
 *
$ php facebook_sites_info.php
results:
Array
(
    [0] => stdClass Object
        (
            [id] => http://yandex.ru
            [shares] => 19196
            [comments] => 7
            [time] => 0.284802
        )

    [1] => stdClass Object
        (
            [id] => http://mail.ru
            [shares] => 510
            [comments] => 13
            [time] => 0.496161
        )

    [2] => stdClass Object
        (
            [id] => http://facebook.com
            [shares] => 14174562
            [comments] => 1331
            [time] => 0.526038
        )

    [3] => stdClass Object
        (
            [id] => http://google.com
            [shares] => 9623503
            [comments] => 10117
            [time] => 0.575178
        )
)
execution time: 0.587344
execution time sum: 1.882179
all comments: 11468
 */

include __DIR__ . '/../vendor/autoload.php';

use MCurl\Client;

$sites = array(
    'google.com',
    'yandex.ru',
    'facebook.com',
    'mail.ru',
);
$results = array();
$all_comments = 0;

$client = new Client;

foreach($sites as $site) {
    $client->add(array(
        CURLOPT_URL => 'https://graph.facebook.com/http://' . $site
    ));
}

$time = microtime(true);
$all_time = 0;
while($result = $client->next()) {
    $info = $result->json;
    $all_time+= $info->time = $result->info['total_time'];

    if (!empty($info->comments)) {
        $all_comments+=$info->comments;
    }

    $results[] = $info;
}

$time-=microtime(true);
$time*=-1;



echo 'results: ', PHP_EOL;
print_r($results);
echo 'execution time: ', sprintf('%.6f',$time), PHP_EOL;
echo 'execution time sum: ', sprintf('%.6f',$all_time), PHP_EOL;
echo 'all comments: ', $all_comments, PHP_EOL;