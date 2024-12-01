<?php

namespace Tests;

use ByJG\RestServer\Exception\ClassNotFoundException;
use ByJG\RestServer\Exception\Error404Exception;
use ByJG\RestServer\Exception\Error405Exception;
use PHPUnit\Framework\TestCase;

class ServerRequestHandlerTest extends TestCase
{
    use MockServerTrait;

    public function testHandle1(): void
    {
        $expectedHeader = [
            "HTTP/1.1 200 OK",
            "Content-Type: application/json",
        ];
        $expectedData = '{"key":"value"}';

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "http://localhost/test";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        $this->processAndGetContent($this->object, $expectedHeader, $expectedData);

        $this->assertTrue($this->reach);
    }

    public function testHandle2(): void
    {
        $expectedHeader = [
            "HTTP/1.1 200 OK",
            "Content-Type: application/json",
        ];
        $expectedData = '{"key":"45"}';

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "http://localhost/test/45";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        $this->processAndGetContent($this->object, $expectedHeader, $expectedData);

        $this->assertEquals(45, $this->reach);
    }

    public function testHandle3(): void
    {
        $this->expectException(Error405Exception::class);
        $expectedData = '{"error":{"type":"Error 405","message":"Method not allowed"}}';

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = "http://localhost/test";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        $this->processAndGetContent($this->object, null, $expectedData);
    }

    public function testHandle4(): void
    {
        $this->expectException(Error404Exception::class);
        $expectedData = '{"error":{"type":"Error 404","message":"Route \'\/doesnotexists\' not found"}}';

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "http://localhost/doesnotexists";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        $this->processAndGetContent($this->object, null, $expectedData);
    }

    public function testHandle5(): void
    {
        $this->expectException(ClassNotFoundException::class);
        $data = [
            "error" => [
                "type" => "Class Not Found",
                "message" => "Class '\\My\\Class' defined in the route is not found"
            ]
        ];
        $expectedData = json_encode($data);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "http://localhost/error";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        $this->processAndGetContent($this->object, null, $expectedData);
    }
}
