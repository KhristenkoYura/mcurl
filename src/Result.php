<?php

namespace MCurl;

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