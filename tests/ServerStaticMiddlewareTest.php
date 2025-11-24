<?php

namespace Tests;

use ByJG\RestServer\Exception\ClassNotFoundException;
use ByJG\RestServer\Exception\Error404Exception;
use ByJG\RestServer\Exception\Error405Exception;
use ByJG\RestServer\Exception\Error415Exception;
use ByJG\RestServer\Exception\Error500Exception;
use ByJG\RestServer\Exception\Error520Exception;
use ByJG\RestServer\Exception\InvalidClassException;
use ByJG\RestServer\Middleware\ServerStaticMiddleware;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ServerStaticMiddlewareTest extends TestCase
{
    use MockServerTrait;

    /**
     * @throws Error404Exception
     * @throws ClassNotFoundException
     * @throws Error405Exception
     * @throws Error520Exception
     * @throws InvalidClassException
     */
    public function testHandle6(): void
    {
        $expectedHeader = [
            "HTTP/1.1 200 OK",
            "content-type: application/json",
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
     * @throws ClassNotFoundException
     * @throws Error405Exception
     * @throws Error520Exception
     * @throws InvalidClassException
     */
    public function testHandle7(): void
    {
        $this->expectException(Error415Exception::class);
        $expectedData = '{"error":{"type":"Error 415","message":"File type not supported"}}';

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "file://" . __DIR__ . "/mimefiles/test.php";
        $_SERVER['SCRIPT_FILENAME'] = __DIR__ . "/mimefiles/test.php";

        $this->processAndGetContent($this->object, null, $expectedData, new ServerStaticMiddleware());
    }

    /**
     * @return string[][]
     */
    public static function mimeDataProvider(): array
    {
        return [
            [ __DIR__ . "/mimefiles/test.json", "application/json"],
            [ __DIR__ . "/mimefiles/test.pdf", "application/pdf"],
            [ __DIR__ . "/mimefiles/test.png", "image/png"],
        ];

    }

    #[DataProvider('mimeDataProvider')]
    /**
     * @param $entry
     * @param $expected
     *
     * @throws Error415Exception
     * @throws Error500Exception
     */
    public function testMimeContentType($entry, $expected): void
    {
        $serverStatic = new ServerStaticMiddleware();
        $this->assertEquals($expected, $serverStatic->mimeContentType($entry));
    }

    public function testFileNotFound(): void
    {
        $serverStatic = new ServerStaticMiddleware();
        $this->assertNull($serverStatic->mimeContentType("test/aaaa"));
    }
}
