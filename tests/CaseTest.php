<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MCurl\Client;


abstract class CaseTest extends PHPUnit_Framework_TestCase {

    protected $domain = 'test.multi.dev';

    /**
     * @var Client
     */
    protected $req;

    public function setUp() {
        $domain = getenv('TEST_DOMAIN');
        if ($domain) {
            $this->domain = $domain;
        }
        $this->req = $this->createReq();
    }

    protected function createReq() {
        $req = new Client();
        $req->setMaxRequest(10);
        return $req;
    }

    protected function url($path) {
        return 'http://' . $this->domain . $path;
    }
}
