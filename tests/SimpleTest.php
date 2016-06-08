<?php

require_once __DIR__ . '/CaseTest.php';


class SimpleTest extends CaseTest {

    public function testSleep() {
        $count = 91;
        $sleep_num = 13;
        $sleep_time = 0.7;

        $this->req->setMaxRequest(11);
        $this->req->setSleep($sleep_num, $sleep_time);


        $urls = array();
        for($i=0; $i<$count;$i++) {
            $urls[] = $this->url('/simple-'.$i.'.txt');
        }

        $time_begin = microtime(true);
        $results  = $this->req->get($urls);
        $time_end = microtime(true);

        $count_sleep =  intval($count/$sleep_num) - ($count%$sleep_num == 0  ? 1 : 0);
        $script_time_sleep = $count_sleep * $sleep_time;

        $script_time_running = $time_end - $time_begin;

        $this->assertEquals($count, count($results));
        $this->assertTrue($script_time_running >= $script_time_sleep);
    }


    public function testGet() {
        $result  = $this->req->get($this->url('/simple.txt'));
        $this->assertEquals('simple', $result->getBody());
        $this->assertEquals(200, $result->getHttpCode());
    }

    public function testGetWithBaseUrl() {
        $this->req->setBaseUrl('http://' . $this->domain);

        $result = $this->req->get('/simple.txt');
        $this->assertEquals('simple', $result->getBody());
        $this->assertEquals(200, $result->getHttpCode());
    }

    public function testHeaders() {
        $this->req->enableHeaders();
        $result  = $this->req->get($this->url('/simple.txt'));
        $this->assertNotEmpty($result->getHeaders());
        $this->assertNotEmpty($result->headers['server']);
    }

    public function testGetAsynchron() {
        $urls = array(
            $this->url('/simple-sleep-4.txt'),
            $this->url('/simple-sleep-1.txt'),
            $this->url('/simple-sleep-2.txt'),
            $this->url('/simple-sleep-3.txt'),
        );
        $time_begin = time();
        $results  = $this->req->get($urls);
        $time_end = time();

        $i=1;
        foreach($results AS $result) {
            $this->assertEquals('sleep-' .$i , $result->getBody());
            $i++;
        }

        $this->assertEquals(4, count($results));
        $this->assertTrue(($time_end - $time_begin) < 6);
    }



    public function testGetSynchron() {
        $req = $this->createReq();
        $req->setMaxRequest(1);

        $urls = array(
            $this->url('/simple-sleep-1.txt'),
            $this->url('/simple-sleep-2.txt'),
            $this->url('/simple-sleep-3.txt'),
        );

        $time_begin = time();
        $results  = $req->get($urls);
        $time_end = time();

        foreach($results AS $i => $result) {
            $this->assertEquals('sleep-' . ($i+1), $result->getBody());
        }

        $this->assertEquals(3, count($results));
        $this->assertTrue(($time_end - $time_begin) >= 6);
    }

    public function testPost() {
        $result  = $this->req->post($this->url('/post.php'), array('data' => 'post data'));
        $this->assertEquals('POST', (string) $result);
    }

    public function testPostWithBaseUrl() {
        $this->req->setBaseUrl('http://' . $this->domain);

        $result = $this->req->post('/post.php', array('data' => 'post data'));
        $this->assertEquals('POST', (string) $result);
    }


    public function testHttp404() {
        $result = $this->req->get($this->url('/simple-error/404.txt'));
        $this->assertTrue($result->hasError());
        $this->assertEquals(404, $result->getErrorCode());
        $this->assertEquals('http', $result->getErrorType());
        $this->assertNotEmpty($result->getError());
    }

    public function testHttp503() {
        $result = $this->req->get($this->url('/simple-error/503.txt'));
        $this->assertTrue($result->hasError());
        $this->assertEquals(503, $result->getErrorCode());
        $this->assertEquals('http', $result->getErrorType());
        $this->assertNotEmpty($result->getError());
    }

    public function testTimeout() {
        $url = $this->url('/simple-sleep-3.txt');
        $timeOut = 1;
        $result = $this->req->get($url, array(CURLOPT_TIMEOUT => $timeOut));
        $this->assertTrue($result->hasError());
        $this->assertEquals('network', $result->getErrorType());
        $this->assertNotEmpty($result->getError());

        $this->assertEquals($timeOut, $result->options[CURLOPT_TIMEOUT]);
        $this->assertEquals($url, $result->options[CURLOPT_URL]);
    }

    public function testJson() {
        $result = $this->req->get($this->url('/simple-json.txt'));
        $data = $result->getJson(true);
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('json_test', $data);
    }
}
