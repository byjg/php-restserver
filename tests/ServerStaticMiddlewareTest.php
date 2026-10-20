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
        $_SERVER['REQUEST_URI'] = "/test.json";
        $_SERVER['SCRIPT_FILENAME'] = __DIR__ . "/mimefiles/app.php";

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
        $_SERVER['REQUEST_URI'] = "/test.php";
        $_SERVER['SCRIPT_FILENAME'] = __DIR__ . "/mimefiles/app.php";

        $this->processAndGetContent($this->object, null, $expectedData, new ServerStaticMiddleware());
    }

    public function testDirectoryIndex(): void
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
        $_SERVER['REQUEST_URI'] = "/";
        $_SERVER['SCRIPT_FILENAME'] = __DIR__ . "/mimefiles/app.php";

        $this->processAndGetContent($this->object, $expectedHeader, $expectedData, new ServerStaticMiddleware('test.json'));
    }

    /**
     * @return string[][]
     */
    public static function outsideRootProvider(): array
    {
        return [
            'parent directory' => ["/../fixtures/swagger-example.json"],
            'encoded parent directory' => ["/%2e%2e/fixtures/swagger-example.json"],
            'absolute file uri' => ["file://" . __DIR__ . "/fixtures/swagger-example.json"],
        ];
    }

    #[DataProvider('outsideRootProvider')]
    public function testFileOutsideRootIsNotServed(string $requestUri): void
    {
        $this->expectException(Error404Exception::class);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = $requestUri;
        $_SERVER['SCRIPT_FILENAME'] = __DIR__ . "/mimefiles/app.php";

        $path = json_encode((new \ByJG\Util\Uri($requestUri))->getPath());
        $expectedData = '{"error":{"type":"Error 404","message":"Route ' . "'" . trim($path, '"') . "'" . ' not found"}}';

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
