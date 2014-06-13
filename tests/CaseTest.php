<?php

require_once '../src/Client.php';

use multiCurl\Client;


abstract class CaseTest extends PHPUnit_Framework_TestCase {

    protected $domain = 'test.multi.dev';

    /**
     * @var Client
     */
    protected $req;

    public function setUp() {
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