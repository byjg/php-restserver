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

    #[\Override]
    public function setup(): void
    {
        $this->object = new HttpResponse();
    }

    #[\Override]
    public function tearDown(): void
    {
        $this->object = null;
    }

    public function testGetHeaders(): void
    {
        $this->assertEquals(
            [],
            $this->object->getHeaders()
        );

        $this->object->addHeader('X-Test', 'OK');

        $this->assertEquals(
            [
                'X-Test' => 'OK',
            ],
            $this->object->getHeaders()
        );

        $this->object->addHeader('X-Test2', 'OK2');

        $this->assertEquals(
            [
                'X-Test' => 'OK',
                'X-Test2' => 'OK2',
            ],
            $this->object->getHeaders()
        );

        $this->object->addHeader('X-Test', 'OK3');

        $this->assertEquals(
            [
                'X-Test' => 'OK3',
                'X-Test2' => 'OK2',
            ],
            $this->object->getHeaders()
        );

        $this->object->addHeader('X-Test2', ['value1', 'value2']);

        $this->assertEquals(
            [
                'X-Test' => 'OK3',
                'X-Test2' => ['value1', 'value2'],
            ],
            $this->object->getHeaders()
        );
    }

    public function testSetResponseCode(): void
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
