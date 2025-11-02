<?php

namespace Tests;

use ByJG\RestServer\Exception\ClassNotFoundException;
use ByJG\RestServer\Exception\Error404Exception;
use ByJG\RestServer\Exception\Error405Exception;
use ByJG\RestServer\Exception\Error422Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ServerRequestHandlerTest extends TestCase
{
    use MockServerTrait;

    /**
     * Data provider for testing allowed content types with strict mode
     */
    public static function allowedContentTypesProvider(): array
    {
        return [
            'JSON' => [
                'application/json',
                [
                    "HTTP/1.1 200 OK",
                    "Content-Type: application/json",
                ],
                '{"key":"value"}'
            ],
            'XML' => [
                'application/xml',
                [
                    "HTTP/1.1 200 OK",
                    "Content-Type: application/xml",
                ],
                "<?xml version=\"1.0\"?>\n<root><key>value</key></root>\n"
            ]
        ];
    }

    /**
     * Test allowed output processors with strict mode
     */
    #[DataProvider('allowedContentTypesProvider')]
    public function testOutputProcessorStrictAllowed(string $contentType, array $expectedHeader, string $expectedData): void
    {
        // Set up the request
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "http://localhost/test/strict";
        $_SERVER['HTTP_ACCEPT'] = $contentType;
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        // Create a custom handler without default output processor
        $handler = clone $this->object;

        // Process the request
        $this->processAndGetContent(
            $handler,
            $expectedHeader,
            $expectedData
        );

        // Verify we reached the endpoint
        $this->assertTrue($this->reach, "Failed to reach endpoint with content type: {$contentType}");
    }

    /**
     * Test not allowed output processor with strict mode
     */
    public function testOutputProcessorStrictNotAllowed(): void
    {
        // Set up the request
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "http://localhost/test/strict";
        $_SERVER['HTTP_ACCEPT'] = 'text/html';
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        // Create a custom handler without default output processor
        $handler = clone $this->object;

        // Expect an exception
        $this->expectException(Error422Exception::class);
        $this->expectExceptionMessage('Accept content not allowed');

        // Process the request - should throw exception
        $this->processAndGetContent(
            $handler,
            null,
            '{"error":{"type":"Error 422","message":"Accept content not allowed"}}'
        );
    }

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

    /**
     * @return list<array{string, string}>
     */
    public static function typesDataProvider(): array
    {
        return [
            [
                'application/json',
                '{"key":"value"}'
            ],
            [
                'application/xml',
                "<?xml version=\"1.0\"?>\n<root><key>value</key></root>\n"
            ],
            [
                'text/html',
                "value\n"
            ],
            [
                'text/csv',
                "key\nvalue\n"
            ],
        ];
    }

    #[DataProvider('typesDataProvider')]
    public function testHandle1Types(string $mimeType, string $expectedData): void
    {
        $expectedHeader = [
            "HTTP/1.1 200 OK",
            "Content-Type: $mimeType",
        ];

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "http://localhost/test";
        $_SERVER['HTTP_ACCEPT'] = $mimeType;
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
