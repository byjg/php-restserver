<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class ServerDummyMiddlewareTest extends TestCase
{
    use MockServerTrait;

    public function testHandle1WithAfterMiddleware()
    {
        $expectedHeader = [
            "HTTP/1.1 200 OK",
            "Content-Type: application/json",
        ];
        $expectedData = '{"key":"value"}';

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "http://localhost/test";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        $this->processAndGetContent($this->object, $expectedHeader, $expectedData, new DummyAfterMiddleware());

        $this->assertTrue($this->reach);
    }
}
