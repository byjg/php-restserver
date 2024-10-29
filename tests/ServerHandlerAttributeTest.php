<?php

namespace Tests;

use ByJG\RestServer\HttpRequestHandler;
use ByJG\RestServer\Route\RouteList;
use PHPUnit\Framework\TestCase;
use Tests\Routes\RouteFromAttributes;

class ServerHandlerAttributeTest extends TestCase
{
    use MockServerTrait;

    public function setup(): void
    {
        ini_set('output_buffering', 4096);
        $this->object = new HttpRequestHandler();
        $this->reach = false;
        $this->definition = new RouteList();

        $this->definition->addClass(RouteFromAttributes::class);
    }

    public function testSanity(): void
    {
        $this->assertCount(2, $this->definition->getRoutes());
    }

    public function testRoute1(): void
    {
        $expectedHeader = [
            "HTTP/1.1 200 OK",
            "Content-Type: application/json",
        ];
        $expectedData = '[{"x":"Before Process"},{"a":"Route 1 get"}]';

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "http://localhost/route1";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        $this->processAndGetContent($this->object, $expectedHeader, $expectedData);
    }

    public function testRoute2(): void
    {
        $expectedHeader = [
            "HTTP/1.1 200 OK",
            "Content-Type: application/json",
        ];
        $expectedData = '[{"a":"Route 1 post"},{"x":"After Process"}]';

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = "http://localhost/route1";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        $this->processAndGetContent($this->object, $expectedHeader, $expectedData);
    }
}
