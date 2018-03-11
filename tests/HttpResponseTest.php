<?php

namespace Tests;

use ByJG\RestServer\HttpResponse;
use PHPUnit\Framework\TestCase;

class HttpResponseTest extends TestCase
{

    /**
     * @var \ByJG\RestServer\HttpResponse
     */
    protected $object;

    public function setUp()
    {
        $this->object = new HttpResponse();
    }

    public function tearDown()
    {
        $this->object = null;
    }

    public function testGetHeaders()
    {
        $this->object->getHeaders();
        $this->assertEquals(
            [],
            $this->object->getHeaders()
        );

        $this->object->addHeader('X-Test', 'OK');

        $this->assertEquals(
            [
                ['X-Test: OK', true]
            ],
            $this->object->getHeaders()
        );

        $this->object->addHeader('X-Test2', 'OK2');

        $this->assertEquals(
            [
                ['X-Test: OK', true],
                ['X-Test2: OK2', true],
            ],
            $this->object->getHeaders()
        );

        $this->object->addHeader('X-Test', 'OK3');

        $this->assertEquals(
            [
                ['X-Test: OK', true],
                ['X-Test: OK3', false],
                ['X-Test2: OK2', true],
            ],
            $this->object->getHeaders()
        );
    }

    public function testSetResponseCode()
    {
        $this->assertEquals(
            200,
            $this->object->getResponseCode()
        );

        $this->object->setResponseCode(302);

        $this->assertEquals(
            302,
            $this->object->getResponseCode()
        );

    }
}
