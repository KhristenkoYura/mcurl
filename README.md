MultiCurl
=========
Simple library for curl multi request

Example
=======
```php
// Create
use MCurl\Client;

$client = new Client();

// simple request
echo $client->get('http://example.com');

// simple asynchron multi request
$results = $client->get(['http://example.com', 'http://example.net']);
foreach($results as $result) {
    echo $result;
}
/**
 * more examples
 * @see tests/
 */

```