MCurl - simple, but functional wrapper for curl
=========
[![Build](https://travis-ci.org/KhristenkoYura/mcurl.svg?branch=master)](https://travis-ci.org/KhristenkoYura/mcurl)
[![Version](https://img.shields.io/packagist/v/khr/php-mcurl-client.svg)](https://packagist.org/packages/khr/php-mcurl-client)
[![License](https://img.shields.io/packagist/l/khr/php-mcurl-client.svg)](https://github.com/KhristenkoYura/mcurl/blob/master/LICENSE)
[![Downloads](https://img.shields.io/packagist/dt/khr/php-mcurl-client.svg)](https://packagist.org/packages/khr/php-mcurl-client)
### Features:
- PHP >= 5.3 (compatible up to version 7.0 && hhvm)
- **stable**. Using many projects
- **fast** request. Minimal overhead
- run a query in a single line
- parallel request (Multi request). Default enable parallel request
- use async request
- **balancing requests**
- no callable 

## Install

The recommended way to install multi curl is through [composer](http://getcomposer.org).

    $ composer require khr/php-mcurl-client:3.*
```json
{
    "require": {
        "khr/php-mcurl-client": "~3.0"
    }
}
```

Quick Start and Examples
=======

### Create
```php
use MCurl\Client;
$client = new Client();
```
### Simple request
```php
echo $client->get('http://example.com');
```
### Check error
```php
$result = $client->get('http://example.com');
echo (!$result->hasError()
    ? 'Ok: ' . $result
    : 'Error: ' .$result->error . ' ('.$result->errorCode.')')
    , PHP_EOL;
```
### Add curl options in request
```php
echo $client->get('http://example.com', [CURLOPT_REFERER => 'http://example.net/']);
```
### Post request
```php
echo $client->post('http://example.com', ['post-key' => 'post-value'], [CURLOPT_REFERER => 'http://example.net/']);
```
### Simple parallel request
```php
// @var $results Result[]
$results = $client->get(['http://example.com', 'http://example.net']);
foreach($results as $result) {
    echo $result;
}
```
### Parallel request
```php 
$urls = ['http://example.com', 'http://example.net', 'http://example.org'];
foreach($urls as $url) {
    $client->add([CURLOPT_URL => $url]);
}
// wait all request
// @var $results Result[]
$results = $client->all();
```
### Parallel request; waiting only next result
```php
$urls = ['http://example.com', 'http://example.net', 'http://example.org'];
foreach($urls as $url) {
    $client->add([CURLOPT_URL => $url]);
}
while($result = $client->next()) {
    echo $result;
}
```
### Dynamic add request
```php
while($result = $client->next()) {
    $urls = fun_get_urls_for_parse_result($result);
    foreach($urls as $url) {
        $client->add([CURLOPT_URL => $url]);
    }
    echo $result;
}
```
### Non-blocking request; use async code; only run request and check done
```php
while($client->run() || $client->has()) {
    while($client->has()) {
        // no blocking
        $result = $client->next();
        echo $result;
    }

    // more async code

    //end more async code
}
```
### Use params
```php
$result = $client->add([CURLOPT_URL => $url], ['id' => 7])->next();
echo $result->params['id']; // echo 7

```
### Result
```php
// @var $result Result
$result->body; // string: body result
$result->json; // object; @see json_encode
$result->getJson(true); // array; @see json_encode
$result->headers['content-type']; // use $client->enableHeaders();
$result->info; // @see curl_getinfo();
$result->info['total_time']; // 0.001

$result->hasError(); // not empty curl_error or http code >=400
$result->hasError('network'); // only not empty curl_error
$result->hasError('http'); // only http code >=400
$result->getError(); // return message error, if ->hasError();
$result->httpCode; // return 200
```
### Config

#### Client::setOptions
This curl options add in all request
```php
$client->setOptions([CURLOPT_REFERER => 'http://example.net/']);
```
#### Client::enableHeaders
Add headers in result
```php
$client->enableHeaders();
```

#### Client::setMaxRequest
The maximum number of queries executed in parallel
```php
$client->setMaxRequest(20); // set 20 parallel request
```
#### Client::setSleep
To balance the requests in the time interval using the method **$client->setSleep**. It will help you to avoid stress (on the sending server) for receiving dynamic content by adjusting the conversion rate in the interval.
Example:
```php
$client->setSleep (20, 1);
```
1 second will run no more than 20 queries.

For static content is recommended restrictions on download speeds, that would not score channel.
Example:
```php
//channel 10 Mb.
$client->setMaxRequest (123);
$client->setOptions([CURLOPT_MAX_RECV_SPEED_LARGE => (10 * 1024 ^ 3) / 123]);
```
### Cookbook

#### Download file
```php
$client->get('http://exmaple.com/image.jpg', [CURLOPT_FILE => fopen('/tmp/image.jpg', 'w')]);
```
#### Save memory
To reduce memory usage, you can write the query result in a temporary file.
```php
$client->setStreamResult(Client::STREAM_FILE); // All Result write in tmp file.
```

```php
/**
 * @see tests/ and source
 */
```
