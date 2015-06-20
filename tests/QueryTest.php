<?php

require_once __DIR__ . '/CaseTest.php';


class QueryTest extends CaseTest {

    public function testWhile() {
        $i=0;
        $urls = array(
            $this->url('/simple-0.txt'),
            $this->url('/simple-1.txt'),
            $this->url('/simple-2.txt'),
            $this->url('/simple-3.txt'),
        );

        foreach($urls AS $url) {
            $this->req->add(array(
                CURLOPT_URL => $url
            ));
        }

        while ($result = $this->req->next()) {
            $i++;
            $this->assertEquals(200, $result->getHttpCode());
            $this->assertNotEmpty($result->getBody());
        }

        $this->assertEquals(count($urls), $i);
    }

    public function testDo() {
        $urls = array(
            $this->url('/simple-0.txt'),
            $this->url('/simple-1.txt'),
            $this->url('/simple-2.txt'),
            $this->url('/simple-3.txt'),
        );

        foreach($urls AS $url) {
            $this->req->add(array(
                CURLOPT_URL => $url
            ));
        }

        do{
            while($this->req->has()) {
                $result = $this->req->next();
                $this->assertEquals(200, $result->getHttpCode());
                $this->assertNotEmpty($result->getBody());
            }
        }while($this->req->run());
    }

    public function testSpider() {
        $this->req->setMaxRequest(10);

        $this->req->add(array(
            CURLOPT_URL => $this->url('/simple-1.txt'),
        ));

        $this->req->add(array(
            CURLOPT_URL => $this->url('/simple-2.txt'),
        ));

        while($this->req->run() || $this->req->has()) {
            while($this->req->has()) {
                $result = $this->req->next();

                $num = (int) substr($result->getBody(), -1, 1);
                if ($num > 4) {
                    continue;
                }

                for($i=0; $i<pow($num, 2);$i++) {
                    $this->req->add(array(
                        CURLOPT_URL => $this->url('/simple-'.($num+1).'.txt'),
                    ));
                }

                $this->assertEquals(200, $result->getHttpCode());
                $this->assertNotEmpty($result->getBody());
            }
        }
    }
}