<?php

namespace Tests;

use ByJG\RestServer\Exception\Error404Exception;
use ByJG\RestServer\Exception\Error415Exception;
use ByJG\RestServer\Middleware\ServerStaticMiddleware;
use PHPUnit\Framework\TestCase;

class ServerStaticMiddlewareTest extends TestCase
{
    use MockServerTrait;

    /**
     * @throws Error404Exception
     * @throws \ByJG\RestServer\Exception\ClassNotFoundException
     * @throws \ByJG\RestServer\Exception\Error405Exception
     * @throws \ByJG\RestServer\Exception\Error520Exception
     * @throws \ByJG\RestServer\Exception\InvalidClassException
     */
    public function testHandle6()
    {
        $expectedHeader = [
            "HTTP/1.1 200 OK",
            "Content-Type: application/json",
        ];
        $expectedData =
            "{\n" .
            '  "key": "file"' . "\n" .
            "}\n";

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "file://" . __DIR__ . "/mimefiles/test.json";
        $_SERVER['SCRIPT_FILENAME'] = __DIR__ . "/mimefiles/test.json";

        $this->processAndGetContent($this->object, $expectedHeader, $expectedData, new ServerStaticMiddleware());

    }

    /**
     * @throws Error404Exception
     * @throws \ByJG\RestServer\Exception\ClassNotFoundException
     * @throws \ByJG\RestServer\Exception\Error405Exception
     * @throws \ByJG\RestServer\Exception\Error520Exception
     * @throws \ByJG\RestServer\Exception\InvalidClassException
     */
    public function testHandle7()
    {
        $this->expectException(Error415Exception::class);
        $expectedData = "[]";

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "file://" . __DIR__ . "/mimefiles/test.php";
        $_SERVER['SCRIPT_FILENAME'] = __DIR__ . "/mimefiles/test.php";

        $this->processAndGetContent($this->object, null, $expectedData, new ServerStaticMiddleware());
    }

    public function mimeDataProvider()
    {
        return [
            [ __DIR__ . "/mimefiles/test.json", "application/json"],
            [ __DIR__ . "/mimefiles/test.pdf", "application/pdf"],
            [ __DIR__ . "/mimefiles/test.png", "image/png"],
        ];

    }

    /**
     * @dataProvider mimeDataProvider
     * @param $entry
     * @param $expected
     * @throws Error404Exception
     */
    public function testMimeContentType($entry, $expected)
    {
        $serverStatic = new ServerStaticMiddleware();
        $this->assertEquals($expected, $serverStatic->mimeContentType($entry));
    }

    public function testFileNotFound()
    {
        $serverStatic = new ServerStaticMiddleware();
        $this->assertNull($serverStatic->mimeContentType("test/aaaa"));
    }
}
