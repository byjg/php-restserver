<?php

namespace Tests;

use ByJG\RestServer\HttpRequest;
use Override;
use PHPUnit\Framework\TestCase;

class HttpRequestTest extends TestCase
{
    /**
     * @var HttpRequest $request
     */
    protected $request;

    #[Override]
    protected function setUp(): void
    {
        $_FILES = [];
        $this->request = new HttpRequest(
            ['getp' => 1],
            ['postp' => 2],
            [
                'HTTP_CONTENT_TYPE' => 'application/json',
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/test/123?a=1',
                'REMOTE_ADDR' => '192.168.1.1',
                'HTTP_USER_AGENT' => 'PHPUnit Test Browser',
                'SERVER_NAME' => 'test.example.com',
                'SERVER_PORT' => '8080',
                'HTTPS' => 'on'
            ],
            ['sessionp' => 4],
            ['cookiep' => 5],
            ['paramp' => 6]
        );
    }

    public function testUploadedFiles(): void
    {
        $_FILES = [
            'test' => [
                'name' => 'test.txt',
                'type' => 'text/plain',
                'size' => 4,
                'tmp_name' => sys_get_temp_dir() . "/tmpfile.txt",
                'error' => UPLOAD_ERR_OK
            ]
        ];
        file_put_contents($_FILES['test']['tmp_name'], 'test');

        $upload = $this->request->uploadedFiles();
        $this->assertEquals(1, $upload->count());
        $this->assertEquals(['test'], $upload->getKeys());
        $this->assertTrue($upload->isOk('test'));
        $this->assertSame(UPLOAD_ERR_OK, $upload->getErrorCode('test'));
        $this->assertEquals('test.txt', $upload->getFileName('test'));
        $this->assertEquals('text/plain', $upload->getFileType('test'));
        $this->assertEquals(4, $upload->getFileSize('test'));
        $this->assertEquals('test', $upload->getUploadedFile('test'));
        $upload->clearTemp('test');
    }

    public function testCookie(): void
    {
        $this->assertEquals(5, $this->request->cookie('cookiep'));
        $this->assertEquals(5, $this->request->request('cookiep'));
        $this->assertEquals(['cookiep' => 5], $this->request->cookie());
    }

    public function testSession(): void
    {
        $this->assertEquals(4, $this->request->session('sessionp'));
        $this->assertEquals(4, $this->request->request('sessionp'));
        $this->assertEquals(['sessionp' => 4], $this->request->session());
    }

    public function testParam(): void
    {
        $this->assertEquals(6, $this->request->param('paramp'));
        $this->assertEmpty($this->request->request('paramp'));
        $this->assertEquals(['paramp' => 6], $this->request->param());
    }

    public function testGetHeader(): void
    {
        $this->assertEquals('application/json', $this->request->getHeader('content-type'));
    }

    public function testGetRequestPath(): void
    {
        $this->assertEquals('/test/123', $this->request->getRequestPath());
    }

    public function testGet(): void
    {
        $this->assertEquals(1, $this->request->get('getp'));
        $this->assertEquals(1, $this->request->request('getp'));
        $this->assertEquals(['getp' => 1], $this->request->get());
    }

    public function testPost(): void
    {
        $this->assertEquals(2, $this->request->post('postp'));
        $this->assertEquals(2, $this->request->request('postp'));
        $this->assertEquals(['postp' => 2], $this->request->post());
    }

    public function testAppendVars(): void
    {
        $this->request->appendVars(['newp' => 7]);
        $this->assertEquals(7, $this->request->param('newp'));
    }

    public function testEmptyRequest(): void
    {
        $request = new HttpRequest([], [], [], [], [], []);
        $this->assertEmpty($request->getRequestPath());
        $this->assertEmpty($request->server('REQUEST_METHOD'));
    }

    public function testGetRequestIp(): void
    {
        // Test with preset REMOTE_ADDR
        $this->assertEquals('192.168.1.1', $this->request->getRequestIp());

        // Test with HTTP_X_FORWARDED_FOR
        $requestWithForwarded = new HttpRequest(
            [],
            [],
            ['HTTP_X_FORWARDED_FOR' => '10.0.0.1,192.168.1.1'],
            [],
            []
        );
        $this->assertEquals('10.0.0.1', $requestWithForwarded->getRequestIp());

        // Test with no IP headers
        $requestWithNoIp = new HttpRequest([], [], [], [], []);
        $this->assertNull($requestWithNoIp->getRequestIp());
    }

    public function testStaticIpMethod(): void
    {
        // Save original $_SERVER
        $originalServer = $_SERVER;

        try {
            // Set a test IP address
            $_SERVER['REMOTE_ADDR'] = '10.0.0.5';
            $ip = HttpRequest::ip();
            $this->assertEquals('10.0.0.5', $ip);
        } finally {
            // Restore original $_SERVER
            $_SERVER = $originalServer;
        }
    }

    public function testGetUserAgent(): void
    {
        $this->assertEquals('PHPUnit Test Browser', $this->request->getUserAgent());

        // Test with no user agent
        $requestWithNoUserAgent = new HttpRequest([], [], [], [], []);
        $this->assertNull($requestWithNoUserAgent->getUserAgent());
    }

    public function testStaticUserAgentMethod(): void
    {
        // Save original $_SERVER
        $originalServer = $_SERVER;

        try {
            // Set a test user agent
            $_SERVER['HTTP_USER_AGENT'] = 'Static Test Browser';
            $userAgent = HttpRequest::userAgent();
            $this->assertEquals('Static Test Browser', $userAgent);
        } finally {
            // Restore original $_SERVER
            $_SERVER = $originalServer;
        }
    }

    public function testGetServerName(): void
    {
        $this->assertEquals('test.example.com', $this->request->getServerName());

        // Test with HTTP_HOST
        $requestWithHttpHost = new HttpRequest(
            [],
            [],
            ['HTTP_HOST' => 'host.example.com'],
            [],
            []
        );
        $this->assertEquals('host.example.com', $requestWithHttpHost->getServerName());

        // Test with SERVER_ADDR as fallback
        $requestWithServerAddr = new HttpRequest(
            [],
            [],
            ['SERVER_ADDR' => '192.168.1.10'],
            [],
            []
        );
        $this->assertEquals('192.168.1.10', $requestWithServerAddr->getServerName());

        // Test with no server information
        $requestWithNoServer = new HttpRequest([], [], [], [], []);
        $this->assertNull($requestWithNoServer->getServerName());
    }

    public function testGetRequestServer(): void
    {
        // Test basic server name
        $this->assertEquals('test.example.com', $this->request->getRequestServer());

        // Test with port
        $this->assertEquals('test.example.com:8080', $this->request->getRequestServer(true));

        // Test with protocol and port
        $this->assertEquals('https://test.example.com:8080', $this->request->getRequestServer(true, true));

        // Test with HTTP protocol
        $requestWithHttp = new HttpRequest(
            [],
            [],
            [
                'SERVER_NAME' => 'http.example.com',
                'SERVER_PORT' => '80',
                'HTTPS' => 'off'
            ],
            [],
            []
        );
        $this->assertEquals('http://http.example.com:80', $requestWithHttp->getRequestServer(true, true));
    }

    public function testPayload(): void
    {
        // We can't easily mock php://input, so we're just verifying the method runs
        $this->request->payload();
        // If we reached here without exception, the test passes
        $this->assertTrue(true);
    }

    public function testRouteMethod(): void
    {
        $this->assertEquals('POST', $this->request->routeMethod());

        // Test default to GET
        $requestNoMethod = new HttpRequest([], [], [], [], []);
        $this->assertEquals('GET', $requestNoMethod->routeMethod());

        // Test with array value (edge case)
        $requestArrayMethod = new HttpRequest(
            [],
            [],
            ['REQUEST_METHOD' => ['PUT', 'POST']],
            [],
            []
        );
        $this->assertEquals('PUT', $requestArrayMethod->routeMethod());

        // Test with boolean value (edge case)
        $requestBoolMethod = new HttpRequest(
            [],
            [],
            ['REQUEST_METHOD' => true],
            [],
            []
        );
        $this->assertEquals('GET', $requestBoolMethod->routeMethod());
    }

    public function testRouteMetadata(): void
    {
        // Initial metadata should be empty
        $this->assertEmpty($this->request->getRouteMetadata());
        $this->assertNull($this->request->getRouteMetadata('test'));

        // Set metadata
        $metadata = ['controller' => 'TestController', 'action' => 'testAction', 'params' => ['id' => 123]];
        $this->request->setRouteMetadata($metadata);

        // Test getRouteMetadata with no key (entire array)
        $this->assertEquals($metadata, $this->request->getRouteMetadata());

        // Test getRouteMetadata with specific keys
        $this->assertEquals('TestController', $this->request->getRouteMetadata('controller'));
        $this->assertEquals('testAction', $this->request->getRouteMetadata('action'));
        $this->assertEquals(['id' => 123], $this->request->getRouteMetadata('params'));

        // Test non-existent key
        $this->assertNull($this->request->getRouteMetadata('nonexistent'));
    }
}
