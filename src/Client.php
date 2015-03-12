<?php

namespace multiCurl;

class Client {

    /**
     * Result write in memory
     */
    const STREAM_MEMORY = 'php://memory';

    /**
     * Result write in temporary files. Dir @see sys_get_temp_dir()
     */
    const STREAM_FILE = 'php://temp/maxmemory:0';

    /**
     * Not exec request
     * @var array
     */
    protected $queries = array();

    /**
     * Not exec count request
     * @var int
     */
    protected $queriesCount = 0;

    /**
     * Exec request
     * @var array
     */
    protected $queriesQueue = array();

    /**
     * Exec count request
     * @var int
     */
    protected $queriesQueueCount = 0;

    /**
     * Curl default  options
     * @see ->addCurlOption(), ->getCurlOption(), ->delCurlOption()
     * @var array
     */
    protected $curlOptions = array(
        CURLOPT_BINARYTRANSFER => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
    );

    /**
     * Max asynchron request
     * @var int
     */
    protected $maxRequest = 10;

    /**
     * Return result class
     * @var int microsecond
     */
    protected $classResult = '\\multiCurl\\Result';

    /**
     * Sleep script undo $this->sleepNext request
     * @var int microsecond
     */
    protected $sleep = 0;

    /**
     * @see $this->sleep
     * @var int
     */
    protected $sleepNext;

    protected $sleepBlocking;

    protected $sleepNextTime;

    /**
     * Count executed request
     * @var int
     */
    protected $count = 0;

    /**
     * Save results
     * @var array
     */
    protected $results = array();

    /**
     * @see curl_multi_init()
     * @var null
     */
    protected $mh;

    /**
     * @var curl_share_init
     */
    protected $sh;

    /**
     * has Request
     * @var bool
     */
    protected $isRunMh = false;

    /**
     * Has use blocking function curl_multi_select
     * @var bool
     */
    protected $isSelect = true;

    /**
     * @example self::STREAM_MEMORY
     * @see http://php.net/manual/ru/wrappers.php
     * @var string
     */
    protected $streamResult = null;

    protected $enableHeaders = false;

    /**
     * @see http://php.net/manual/ru/stream.filters.php
     * @var array
     */
    protected $streamFilters = array();

    public function __construct() {
        $this->mh = curl_multi_init();
    }

    /**
     * This Asynchron request if maxRequest > 1
     * $client->get('http://example.com/1.php') => return Result
     * Or $client->get('http://example.com/1.php', 'http://example.com/2.php') => return Result[]
     * Or $client->get(['http://example.com/1.php', 'http://example.com/2.php'], [CURLOPT_MAX_RECV_SPEED_LARGE => 1024]) => return Result[]
     *
     * @param string|array $url
     * @param array $opts @see http://www.php.net/manual/ru/function.curl-setopt.php
     * @return Result|Result[]|null
     */
    public function get($url, $opts = array()) {
        $urls = (array) $url;
        foreach ($urls AS $id => $u) {
            $opts[CURLOPT_URL] = $u;
            $this->add($opts, $id);
        }
        return is_array($url) ? $this->all() :  $this->next();
    }

    /**
     * @see $this->get
     * @param $url
     * @param array $data post data
     * @param array $opts
     * @return Result|Result[]|null
     */
    public function post($url, $data = array(), $opts = array()) {
        $opts[CURLOPT_POST] = true;
        $opts[CURLOPT_POSTFIELDS] = $data;
        return $this->get($url,$opts);
    }

    /**
     * Add request
     * @param array $opts Options curl. Example: array( CURLOPT_URL => 'http://example.com' );
     * @param array|string $params All data, require binding to the request or if string: identity request
     * @return bool
     */
    public function add($opts = array(), $params = array()) {
        $id = null;

        if (is_string($params)) {
            $id = $params;
            $params = array();
        }

        if (isset($this->streamResult) && !isset($opts[CURLOPT_FILE])) {
            $opts[CURLOPT_FILE] = fopen($this->streamResult, 'r+');
            if ( !$opts[CURLOPT_FILE] ) {
                return false;
            }
        }

        if (!empty($this->streamFilters ) && isset($opts[CURLOPT_FILE])) {
            foreach ($this->streamFilters AS $filter) {
                stream_filter_append( $opts[CURLOPT_FILE], $filter );
            }
        }

        if (!isset($opts[CURLOPT_WRITEHEADER]) && $this->enableHeaders) {
            $opts[CURLOPT_WRITEHEADER] = fopen(self::STREAM_MEMORY, 'r+');
            if (!$opts[CURLOPT_WRITEHEADER]) {
                return false;
            }
        }

        $query = array(
            'id' => $id,
            'opts' => $opts,
            'params' => $params
        );


        $this->queries[] = $query;
        $this->queriesCount++;

        return true;
    }

    /**
     * Set wrappers
     * @see self::STREAM_*
     * Default: self::STREAM_MEMORY
     * @see http://php.net/manual/ru/wrappers.php
     * @param string $stream
     */
    public function setStreamResult( $stream ) {
        $this->streamResult = $stream;
    }

    /**
     * Set stream filters
     * @see http://php.net/manual/ru/stream.filters.php
     * @example  array( 'string.strip_tags', 'string.tolower' )
     * @param array $filters Registered Stream Filters
     * @return Client
     */
    public function setStreamFilters( array $filters ) {
        $this->streamFilters = $filters;
        return $this;
    }

    /**
     * Enable headers in result. Default false
     * @param bool $enable
     */
    public function enableHeaders($enable = true) {
        $this->enableHeaders = $enable;
    }
    /**
     * Set default curl options
     * @example: [
     *  CURLOPT_TIMEOUT => 10,
     *  CURLOPT_COOKIEFILE => '/path/to/cookie.txt',
     *  CURLOPT_COOKIEJAR => '/path/to/cookie.txt',
     *  ...
     * ]
     * @param array $values
     */
    public function setCurlOption($values) {
        foreach($values AS $key => $value) {
            $this->curlOptions[$key] = $value;
        }
    }

    /**
     * @see curl_share_setopt
     * @link http://php.net/manual/en/function.curl-share-setopt.php
     * @param $option
     * @param $value
     */
    public function setShareOptions($option, $value) {
        if (!isset($this->sh)) {
            $this->sh = curl_share_init();
            $this->setCurlOption(array(CURLOPT_SHARE => $this->sh));
        }

        curl_share_setopt($this->sh, $option, $value);
    }

    /**
     * Max request in Asynchron query
     * @param $max int default:10
     * @return void
     */
    public function setMaxRequest( $max ) {
        $this->maxRequest = $max;
        // PHP 5 >= 5.5.0
        if (function_exists('curl_multi_setopt')) {
            curl_multi_setopt($this->mh, CURLMOPT_MAXCONNECTS, $max);
        }
    }

    /**
     * @param $next int
     * @param $sleep float second
     * @param $blocking bool
     */
    public function setSleep($next, $second = 1.0, $blocking = true) {
        $this->sleep = $second;
        $this->sleepNext = $next;
        $this->sleepBlocking = $blocking;
    }

    /**
     * Return count query
     * @return int
     */
    public function getCountQuery() {
        return $this->queriesCount;
    }

    /**
     * Exec cURL resource
     * @return bool
     */
    public function run() {
        if ( $this->isRunMh ) {
            $this->exec();
            $this->execRead();
            return ($this->processedQuery() || $this->queriesQueueCount > 0) ? true : ( $this->isRunMh = false );
        }

        return $this->processedQuery();
    }


    /**
     * Return all results; wait all request
     * @return Result|null
     */
    public function all() {
        while($this->run()) {}
        $results = $this->results;
        $this->results = array();
        return $results;
    }

    /**
     * Return one next result, wait first exec request
     * @return Result|null
     */
    public function next() {
        while(empty($this->results) && $this->run()) {}
        return array_pop($this->results);
    }

    /**
     * Check has one result
     * @return bool
     */
    public function has() {
        return !empty($this->results);
    }


    /**
     * Clear result request
     * @return void
     */
    public function clear() {
        $this->results = array();
    }

    /**
     * Set class result
     * @return void
     */
    public function setClassResult($name) {
        $this->classResult = $name;
    }

    /**
     * @see $this->isSelect
     * @param bool $select
     */
    public function isSelect($select) {
        $this->isSelect = $select;
    }

    protected function processedResponse($id) {
        $this->queriesQueueCount--;
        $this->count++;
        $query = $this->queriesQueue[$id];

        $result = new $this->classResult($query);
        if (isset($query['id'])) {
            $this->results[$query['id']] = $result;
        } else {
            $this->results[] = $result;
        }

        curl_multi_remove_handle( $this->mh, $query['ch'] );
        unset($this->queriesQueue[$id]);

        return true;
    }

    protected function processedQuery() {
        // not query
        if ( $this->queriesCount == 0 ) {
            return false;
        }

        $count = $this->maxRequest - $this->queriesQueueCount;

        if ($this->sleep !== 0) {
            $modulo_begin = $this->count % $this->sleepNext;
            $modulo_end = ($this->count + $count) % $this->sleepNext;

            $current_time = microtime(true);
            if (!isset($this->sleepNextTime)) {
                $this->sleepNextTime = $current_time - $this->sleep;
            }
            $sleep_time = (int) (($this->sleep - ($current_time - $this->sleepNextTime))*1000000);

            if ($sleep_time > 0) {
                if ($modulo_begin === 0) {
                    if ($this->sleepBlocking) {
                        usleep($sleep_time);
                        $sleep_time = 0;
                        $current_time = microtime(true);
                    } else {
                        $count = 0;
                    }
                } elseif($modulo_begin >= $modulo_end) {
                    $count-= $modulo_end;
                }
            }

            if ($sleep_time <= 0 && ($modulo_begin === 0 || $modulo_begin >= $modulo_end)) {
                $this->sleepNextTime = $current_time;
            }
        }

        if ($count > 0) {
            $limit = $this->queriesCount < $count ? $this->queriesCount : $count;

            $this->queriesCount-= $limit;
            $this->queriesQueueCount+= $limit;
            while($limit--) {
                $key = key($this->queries);
                $query = $this->queries[$key];
                unset($this->queries[$key]);

                $query['ch'] = curl_init();
                curl_setopt_array( $query['ch'], $this->curlOptions + $query['opts'] );

                curl_multi_add_handle( $this->mh, $query['ch'] );
                $id = $this->getResourceId( $query['ch'] );
                $this->queriesQueue[$id] = $query;
            }
        }

        return $this->isRunMh = true;
    }

    protected function exec() {
        do {
            $mrc = curl_multi_exec( $this->mh, $active );
        } while ( $mrc == CURLM_CALL_MULTI_PERFORM || ($this->isSelect && curl_multi_select( $this->mh, 0.01 ) > 0) );
    }

    protected function execRead() {
        while(($info = curl_multi_info_read($this->mh, $active)) !== false) {
            if ( $info['msg'] === CURLMSG_DONE ) {
                $id = $this->getResourceId( $info['handle'] );
                $this->processedResponse($id);
            }
        }
    }


    protected function getResourceId( $resource ) {
        return intval( $resource );
    }

    public function __destruct() {
        curl_multi_close( $this->mh );
    }
}


class Result {

    /**
     * @see get{Name}()
     *
     * @var mixed $id
     * @var resource $ch
     * @var array $info
     * @var array $options
     * @var int $httpCode
     * @var string $body
     * @var resource $bodyStream
     * @var \stdClass|null $json
     * @var array $headers
     * @var array $params
     * @var bool $hasError
     * @var string $errorType
     * @var string $error
     * @var int $errorCode
     *
     */

    /**
     * @var array
     */
    protected $query;

    /**
     * @var array
     */
    protected $rawHeaders;

    public function __construct($query) {
        $this->query = $query;
    }

    /**
     * Return id in request
     * @return null|mixed
     */
    public function getId() {
        return isset($this->query['id']) ? $this->query['id'] : null;
    }

    /**
     * cURL session: curl_init()
     * @return resource
     */
    public function getCh() {
        return $this->query['ch'];
    }

    /**
     * @see curl_getinfo();
     * @return mixed
     */
    public function getInfo() {
        return curl_getinfo($this->query['ch']);
    }

    /**
     * Return curl option in request
     * @return array
     */
    public function getOptions() {
        $opts = $this->query['opts'];
        unset($opts[CURLOPT_FILE]);
        if (isset($opts[CURLOPT_WRITEHEADER])) {
            unset($opts[CURLOPT_WRITEHEADER]);
        }
        return $opts;
    }
    /**
     * Result http code
     * @see curl_getinfo($ch, CURLINFO_HTTP_CODE)
     * @return int
     */
    public function getHttpCode() {
        return (int) curl_getinfo($this->query['ch'], CURLINFO_HTTP_CODE);
    }

    /**
     * Example:
     * $this->getHeaders() =>
     * return [
     *  'result' => 'HTTP/1.1 200 OK',
     *  'content-type' => 'text/html',
     *  'content-length' => '1024'
     *  ...
     * ];
     *
     * Or $this->headers['content-type'] => return 'text/html' @see $this->__get()
     * @return array
     */
    public function getHeaders() {
        if (!isset($this->rawHeaders) && isset($this->query['opts'][CURLOPT_WRITEHEADER])) {
            rewind($this->query['opts'][CURLOPT_WRITEHEADER]);
            $headersRaw = stream_get_contents($this->query['opts'][CURLOPT_WRITEHEADER]);
            $headers = explode("\n", rtrim($headersRaw));
            $this->rawHeaders['result'] = trim(array_shift($headers));

            foreach ($headers AS $header) {
                list($name, $value) = array_map('trim', explode(':', $header, 2));
                $name = strtolower($name);
                $this->rawHeaders[$name] = $value;
            }
        }
        return $this->rawHeaders;
    }

    /**
     * Result in request
     * @return string
     */
    public function getBody() {
        if (isset($this->query['opts'][CURLOPT_FILE])) {
            rewind($this->query['opts'][CURLOPT_FILE]);
            return stream_get_contents($this->query['opts'][CURLOPT_FILE]);
        } else {
            return curl_multi_getcontent($this->query['ch']);
        }
    }

    /**
     *
     * @return mixed
     */
    public function getBodyStream() {
        rewind($this->query['opts'][CURLOPT_FILE]);
        return $this->query['opts'][CURLOPT_FILE];
    }

    /**
     * @see json_decode
     * @return mixed
     */
    public function getJson() {
        $args = func_get_args();
        if (empty($args)) {
            return @json_decode($this->getBody());
        } else {
            array_unshift($args, $this->getBody());
            return @call_user_func_array('json_decode', $args);
        }
    }

    /**
     * return params request
     * @return mixed
     */
    public function getParams() {
        return $this->query['params'];
    }


    /**
     * Has error
     * @param null|string $type  use: network|http
     * @return bool
     */
    public function hasError($type = null) {
        $errorType = $this->getErrorType();
        return (isset($errorType) && ($errorType == $type || !isset($type)));
    }

    /**
     * Return network if has curl error or http if http code >=400
     * @return null|string return string: network|http or null if not error
     */
    public function getErrorType() {
        if (curl_error($this->query['ch'])) {
            return 'network';
        }

        if ($this->getHttpCode() >= 400) {
            return 'http';
        }

        return null;
    }

    /**
     * Return message error
     * @return null|string
     */
    public function getError() {
        $message = null;
        switch($this->getErrorType()) {
            case 'network':
                $message = curl_error($this->query['ch']);
                break;
            case 'http':
                $message = 'http error ' . $this->getHttpCode();
                break;
        }
        return $message;
    }

    /**
     * Return code error
     * @return int|null
     */
    public function getErrorCode() {
        $number = null;
        switch($this->getErrorType()) {
            case 'network':
                $number = (int) curl_errno($this->query['ch']);
                break;
            case 'http':
                $number = $this->getHttpCode();
                break;
        }
        return $number;
    }

    public function __toString() {
        return $this->getBody();
    }

    /**
     * Simple get result
     * @Example: $this->id, $this->body, $this->error, $this->hasError, $this->headers['content-type'], ...
     *
     * @param $key
     * @return null
     */
    public function __get($key) {
        $method = 'get' . $key;
        return method_exists($this, $method) ? $this->$method() : null;
    }

    public function __destruct() {
        if (isset($this->query['opts'][CURLOPT_FILE]) && is_resource($this->query['opts'][CURLOPT_FILE]))  {
            fclose($this->query['opts'][CURLOPT_FILE]);
        }

        if (isset($this->query['opts'][CURLOPT_WRITEHEADER]) && is_resource($this->query['opts'][CURLOPT_WRITEHEADER]))  {
            fclose($this->query['opts'][CURLOPT_WRITEHEADER]);
        }

        if (is_resource($this->query['ch']))  {
            curl_close($this->query['ch']);
        }
    }
}
