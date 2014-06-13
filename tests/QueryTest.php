<?php

require_once 'CaseTest.php';


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
            $this->req->addQuery(array(
                CURLOPT_URL => $url
            ));
        }

        while ($this->req->hasResults()) {
            $results = $this->req->getResults();
            foreach($results AS $result) {
                $i++;
                $this->assertEquals(200, $result->getHttpCode());
                $this->assertNotEmpty($result->getBody());
            }
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

        do{
            if (!empty($urls)) {
                $this->req->addQuery(array(
                    CURLOPT_URL => array_pop($urls)
                ));

                $this->req->run();
            }

            $results = $this->req->getResults();
            foreach($results AS $result) {
                $this->assertEquals(200, $result->getHttpCode());
                $this->assertNotEmpty($result->getBody());
            }

        }while($this->req->hasResults() || !empty($urls));
    }

    public function testSpider() {
        $this->req->setMaxRequest(10);

        $this->req->addQuery(array(
            CURLOPT_URL => $this->url('/simple-1.txt'),
        ));

        $this->req->addQuery(array(
            CURLOPT_URL => $this->url('/simple-2.txt'),
        ));

        while($this->req->hasResults()) {
            $results = $this->req->getResults();
            foreach($results AS $result) {
                $num = (int) substr($result->getBody(), -1, 1);
                if ($num > 4) {
                    continue;
                }

                for($i=0; $i<pow($num, 2);$i++) {
                    $this->req->addQuery(array(
                        CURLOPT_URL => $this->url('/simple-'.($num+1).'.txt'),
                    ));
                }

                $this->assertEquals(200, $result->getHttpCode());
                $this->assertNotEmpty($result->getBody());
            }
        }
    }


}