<?php

namespace Tests\Psr7;

use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\Psr7\Psr7RequestAdapter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class Psr7RequestAdapterTest extends TestCase
{
    public function testFromHttpRequestBasic(): void
    {
        $httpRequest = new HttpRequest(
            ['page' => '1', 'limit' => '10'],
            ['name' => 'John', 'email' => 'john@example.com'],
            [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/api/users?page=1&limit=10',
                'SERVER_NAME' => 'example.com',
                'SERVER_PORT' => '443',
                'HTTPS' => 'on',
                'HTTP_CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer token123',
                'CONTENT_LENGTH' => '42',
            ],
            ['user_id' => '123'],
            ['session_token' => 'abc123']
        );

        $psr7Request = Psr7RequestAdapter::fromHttpRequest($httpRequest);

        $this->assertInstanceOf(ServerRequestInterface::class, $psr7Request);
        $this->assertEquals('POST', $psr7Request->getMethod());
        $this->assertEquals('/api/users', $psr7Request->getUri()->getPath());
        $this->assertEquals('page=1&limit=10', $psr7Request->getUri()->getQuery());
        $this->assertEquals('https', $psr7Request->getUri()->getScheme());
        $this->assertEquals('example.com', $psr7Request->getUri()->getHost());
    }

    public function testFromHttpRequestHeaders(): void
    {
        $httpRequest = new HttpRequest(
            [],
            [],
            [
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/test',
                'SERVER_NAME' => 'localhost',
                'HTTP_X_CUSTOM_HEADER' => 'custom-value',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_USER_AGENT' => 'PHPUnit/Test',
                'CONTENT_TYPE' => 'text/plain',
            ],
            [],
            []
        );

        $psr7Request = Psr7RequestAdapter::fromHttpRequest($httpRequest);

        $this->assertEquals(['custom-value'], $psr7Request->getHeader('X-Custom-Header'));
        $this->assertEquals(['application/json'], $psr7Request->getHeader('Accept'));
        $this->assertEquals(['PHPUnit/Test'], $psr7Request->getHeader('User-Agent'));
        $this->assertEquals(['text/plain'], $psr7Request->getHeader('Content-Type'));
    }

    public function testFromHttpRequestQueryParams(): void
    {
        $httpRequest = new HttpRequest(
            ['search' => 'test', 'category' => 'books'],
            [],
            [
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/search?search=test&category=books',
                'SERVER_NAME' => 'localhost',
            ],
            [],
            []
        );

        $psr7Request = Psr7RequestAdapter::fromHttpRequest($httpRequest);

        $queryParams = $psr7Request->getQueryParams();
        $this->assertEquals('test', $queryParams['search']);
        $this->assertEquals('books', $queryParams['category']);
    }

    public function testFromHttpRequestParsedBody(): void
    {
        $httpRequest = new HttpRequest(
            [],
            ['username' => 'testuser', 'password' => 'secret'],
            [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/login',
                'SERVER_NAME' => 'localhost',
            ],
            [],
            []
        );

        $psr7Request = Psr7RequestAdapter::fromHttpRequest($httpRequest);

        $parsedBody = $psr7Request->getParsedBody();
        $this->assertEquals('testuser', $parsedBody['username']);
        $this->assertEquals('secret', $parsedBody['password']);
    }

    public function testFromHttpRequestWithPayload(): void
    {
        // Create a mock HttpRequest with payload
        $httpRequest = $this->getMockBuilder(HttpRequest::class)
            ->setConstructorArgs([
                [],
                [],
                [
                    'REQUEST_METHOD' => 'PUT',
                    'REQUEST_URI' => '/api/resource/123',
                    'SERVER_NAME' => 'localhost',
                ],
                [],
                []
            ])
            ->onlyMethods(['payload'])
            ->getMock();

        $httpRequest->method('payload')
            ->willReturn('{"id": 123, "name": "Test"}');

        $psr7Request = Psr7RequestAdapter::fromHttpRequest($httpRequest);

        $body = $psr7Request->getBody()->__toString();
        $this->assertEquals('{"id": 123, "name": "Test"}', $body);
    }

    public function testFromHttpRequestWithRouteParams(): void
    {
        $httpRequest = new HttpRequest(
            [],
            [],
            [
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/users/123/posts/456',
                'SERVER_NAME' => 'localhost',
            ],
            [],
            [],
            ['user_id' => '123', 'post_id' => '456']
        );

        $psr7Request = Psr7RequestAdapter::fromHttpRequest($httpRequest);

        $this->assertEquals('123', $psr7Request->getAttribute('user_id'));
        $this->assertEquals('456', $psr7Request->getAttribute('post_id'));
    }

    public function testFromHttpRequestWithRouteMetadata(): void
    {
        $httpRequest = new HttpRequest(
            [],
            [],
            [
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/api/test',
                'SERVER_NAME' => 'localhost',
            ],
            [],
            []
        );

        $metadata = ['controller' => 'TestController', 'action' => 'index'];
        $httpRequest->setRouteMetadata($metadata);

        $psr7Request = Psr7RequestAdapter::fromHttpRequest($httpRequest);

        $this->assertEquals($metadata, $psr7Request->getAttribute('_route_metadata'));
    }

    public function testFromHttpRequestServerParams(): void
    {
        $serverParams = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/test',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => '8080',
            'REMOTE_ADDR' => '127.0.0.1',
        ];

        $httpRequest = new HttpRequest([], [], $serverParams, [], []);

        $psr7Request = Psr7RequestAdapter::fromHttpRequest($httpRequest);

        $this->assertEquals($serverParams, $psr7Request->getServerParams());
    }

    public function testFromHttpRequestCookieParams(): void
    {
        $httpRequest = new HttpRequest(
            [],
            [],
            [
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/test',
                'SERVER_NAME' => 'localhost',
            ],
            [],
            ['session_id' => 'abc123', 'preferences' => 'dark_mode']
        );

        $psr7Request = Psr7RequestAdapter::fromHttpRequest($httpRequest);

        $cookieParams = $psr7Request->getCookieParams();
        $this->assertEquals('abc123', $cookieParams['session_id']);
        $this->assertEquals('dark_mode', $cookieParams['preferences']);
    }

    public function testFromHttpRequestHttpsDetection(): void
    {
        // Test HTTPS via HTTPS variable
        $httpRequest1 = new HttpRequest(
            [],
            [],
            [
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/test',
                'SERVER_NAME' => 'example.com',
                'HTTPS' => 'on',
            ],
            [],
            []
        );

        $psr7Request1 = Psr7RequestAdapter::fromHttpRequest($httpRequest1);
        $this->assertEquals('https', $psr7Request1->getUri()->getScheme());

        // Test HTTPS via port 443
        $httpRequest2 = new HttpRequest(
            [],
            [],
            [
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/test',
                'SERVER_NAME' => 'example.com',
                'SERVER_PORT' => '443',
            ],
            [],
            []
        );

        $psr7Request2 = Psr7RequestAdapter::fromHttpRequest($httpRequest2);
        $this->assertEquals('https', $psr7Request2->getUri()->getScheme());

        // Test HTTP
        $httpRequest3 = new HttpRequest(
            [],
            [],
            [
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/test',
                'SERVER_NAME' => 'example.com',
                'SERVER_PORT' => '80',
            ],
            [],
            []
        );

        $psr7Request3 = Psr7RequestAdapter::fromHttpRequest($httpRequest3);
        $this->assertEquals('http', $psr7Request3->getUri()->getScheme());
    }

    public function testFromHttpRequestCustomPort(): void
    {
        $httpRequest = new HttpRequest(
            [],
            [],
            [
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/test',
                'HTTP_HOST' => 'example.com:8080',
                'SERVER_PORT' => '8080',
            ],
            [],
            []
        );

        $psr7Request = Psr7RequestAdapter::fromHttpRequest($httpRequest);

        $this->assertEquals(8080, $psr7Request->getUri()->getPort());
        $this->assertEquals('example.com', $psr7Request->getUri()->getHost());
    }
}
