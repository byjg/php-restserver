<?php

namespace Tests;

use ByJG\RestServer\Exception\Error401Exception;
use ByJG\RestServer\Middleware\CorsMiddleware;
use PHPUnit\Framework\TestCase;

class ServerCorsMiddlewareTest extends TestCase
{
    use MockServerTrait;

    public function testHandleCors(): void
    {
        $expectedHeader = [
            "HTTP/1.1 200 OK",
            "Content-Type: application/json",
            "Access-Control-Allow-Origin: http://localhost",
            "Access-Control-Allow-Credentials: true",
            "Access-Control-Max-Age: 86400"
        ];
        $expectedData = "[\"Success!\"]";

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "http://localhost/corstest/tCors";
        $_SERVER['HTTP_ORIGIN'] = "http://localhost";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        $this->processAndGetContent($this->object, $expectedHeader, $expectedData, new CorsMiddleware());

        $this->assertEquals('tCors', $this->reach);
    }

    public function testHandleCorsOptions(): void
    {
        $expectedHeader = [
            "HTTP/1.1 204 No Content",
            "Content-Type: application/json",
            "Access-Control-Allow-Origin: http://localhost",
            "Access-Control-Allow-Credentials: true",
            "Access-Control-Max-Age: 86400",
            "Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE,PATCH",
            "Access-Control-Allow-Headers: Authorization,Content-Type,Accept,Origin,User-Agent,Cache-Control,Keep-Alive,X-Requested-With,If-Modified-Since",
        ];
        $expectedData = "";

        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
        $_SERVER['REQUEST_URI'] = "http://localhost/corstest/tCors";
        $_SERVER['HTTP_ORIGIN'] = "http://localhost";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        $this->processAndGetContent($this->object, $expectedHeader, $expectedData, (new CorsMiddleware())->withCorsOrigins(["server\.com", "localhost"]));
    }

    public function testFailedCorsWrongAllowedServer(): void
    {
        $this->expectException(Error401Exception::class);
        $this->expectExceptionMessage("CORS verification failed. Request Blocked.");

        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
        $_SERVER['REQUEST_URI'] = "http://localhost/corstest/tCors";
        $_SERVER['HTTP_ORIGIN'] = "http://localhost";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        $this->processAndGetContent(
            $this->object,
            null,
            '{"error":{"type":"Error 401","message":"CORS verification failed. Request Blocked."}}',
            (new CorsMiddleware())->withCorsOrigins("anotherhost")
        );
    }

    public function testDefaultCorsSetup(): void
    {
        $expectedHeader = [
            "HTTP/1.1 200 OK",
            "Content-Type: application/json",
            "Access-Control-Allow-Origin: http://anyhostisallowed",
            "Access-Control-Allow-Credentials: true",
            "Access-Control-Max-Age: 86400"
        ];
        $expectedData = "[\"Success!\"]";

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "http://localhost/corstest/tCors";
        $_SERVER['HTTP_ORIGIN'] = "http://anyhostisallowed";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        $this->processAndGetContent($this->object, $expectedHeader, $expectedData, new CorsMiddleware());

        $this->assertEquals('tCors', $this->reach);
    }

    public function testCorsDisabled(): void
    {
        $expectedHeader = [
            "HTTP/1.1 200 OK",
            "Content-Type: application/json",
        ];
        $expectedData = "[\"Success!\"]";

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "http://localhost/corstest/tCors";
        $_SERVER['HTTP_ORIGIN'] = "http://anyhostisallowed";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        $this->processAndGetContent($this->object, $expectedHeader, $expectedData);
    }
}
