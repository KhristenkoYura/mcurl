MultiCurl
=========
Simple library for curl multi request

## Install

The recommended way to install multi curl is through [composer](http://getcomposer.org).

```
{
    "require": {
        "khr/php-mcurl-client": "*"
    }
}
```

Example
=======
```php
// Create
use MCurl\Client;

$client = new Client();

// simple request
echo $client->get('http://example.com');

// add curl options in request
echo $client->get('http://example.com', [CURLOPT_REFERER => 'http://example.net/']);

// post request
echo $client->get('http://example.com', ['post-key' => 'post-value'], [CURLOPT_REFERER => 'http://example.net/']);

// simple parallel request
// @var Result[]
$results = $client->get(['http://example.com', 'http://example.net']);
foreach($results as $result) {
    echo $result;
}

// parallel request
$urls = ['http://example.com', 'http://example.net', 'http://example.org'];
foreach($urls as $url) {
    $client->add([CURLOPT_URL => $url]);
}

// wait all request
// @var Result[]
$results = $client->all();

// or wait next result
while($result = $client->next()) {
    echo $result;
}

// dynamic add request
while($result = $client->next()) {
    $urls = fun_get_urls_for_result($result);
    foreach($urls as $url) {
        $client->add([CURLOPT_URL => $url]);
    }
    echo $result;
}

//async code
while($client->run() || $client->has()) {
    while($client->has()) {
        // no blocking
        $result = $client->next();
        echo $result;
    }

    // more async code

    //end more async code
}

//use params
$client->add([CURLOPT_URL => $url], ['id' => 7]);
$result = $client->next();
echo $result->params['id']; // echo 7

// Result

// @var $result Result
$result->body; // string: body result
$result->json; // or $result->getJson(true) @see json_encode
$result->headers['content-type']; // use $client->enableHeaders();
$result->info; // @see curl_getinfo();

$result->hasError(); // curl_error or http code >=400
$result->getError(); // return message error
$result->httpCode; // return 200

//Config

$client->setMaxRequest(20); // set parallel request
$client->setSleep(20, 1); // 20 request in 1 second
$client->enableHeaders(); // add headers in result



/**
 * more examples
 * @see tests/
 */

```