<?php

namespace Tests;

use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\MockServer;
use ByJG\RestServer\OutputProcessor\JsonOutputProcessor;
use ByJG\RestServer\Route\Route;
use ByJG\RestServer\Route\RouteList;
use ByJG\Util\Uri;
use ByJG\WebRequest\Helper\RequestFormUrlEncoded;
use ByJG\WebRequest\Helper\RequestMultiPart;
use ByJG\WebRequest\MultiPartItem;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * MockServer handles a client PSR-7 request as a web server would: the request object and, while the
 * request is handled, PHP's globals describe it.
 */
class MockServerTest extends TestCase
{
    /** @var array<string, mixed> what the route saw */
    private array $seen = [];

    #[Override]
    public function setUp(): void
    {
        $_SERVER = ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/before'];
        $_GET = ['before' => '1'];
        $_POST = [];
        $_REQUEST = [];
        $_COOKIE = [];
        $_FILES = [];
    }

    private function routes(): RouteList
    {
        $record = function (HttpResponse $response, HttpRequest $request) {
            $uploads = $request->uploadedFiles();
            $this->seen = [
                'body' => $request->body(),
                'query' => $request->query(),
                'cookie' => $request->cookie(),
                'files' => array_map(fn(string $key) => [
                    $uploads->getFileName($key), $uploads->getFileType($key), $uploads->getUploadedFile($key),
                ], array_combine($uploads->getKeys(), $uploads->getKeys())),
                'globals' => [
                    'method' => $_SERVER['REQUEST_METHOD'] ?? null,
                    'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null,
                    'server_port' => $_SERVER['SERVER_PORT'] ?? null,
                    'get' => $_GET,
                    'post' => $_POST,
                    'request' => $_REQUEST,
                    'cookie' => $_COOKIE,
                    'files' => array_map(fn(array $file) => [
                        $file['name'], $file['type'], file_get_contents($file['tmp_name']), $file['error'], $file['size'],
                    ], $_FILES),
                    'tmp_names' => array_column($_FILES, 'tmp_name'),
                ],
            ];
            $response->write(['ok' => true]);
        };

        return (new RouteList())->setRoutes([
            Route::post('/form')->withOutputProcessor(JsonOutputProcessor::class)->withClosure($record),
        ]);
    }

    public function testMultipart(): void
    {
        $request = RequestMultiPart::build(new Uri('http://localhost/form?foo=bar'), 'POST', [
            new MultiPartItem('note', 'somenote'),
            new MultiPartItem('upfile', file_get_contents(__DIR__ . '/mimefiles/test.png'), 'penguim.png', 'image/png'),
        ]);

        $mock = MockServer::mock($this->routes(), $request);

        $this->assertEquals(200, $mock->getPsr7Response()->getStatusCode());
        $png = file_get_contents(__DIR__ . '/mimefiles/test.png');
        $this->assertEquals(['note' => 'somenote'], $this->seen['body']);
        $this->assertEquals(['foo' => 'bar'], $this->seen['query']);
        $this->assertEquals(['upfile' => ['penguim.png', 'image/png', $png]], $this->seen['files']);

        // The globals described the request while it was handled
        $globals = $this->seen['globals'];
        $this->assertEquals('POST', $globals['method']);
        $this->assertEquals('127.0.0.1', $globals['remote_addr']);
        $this->assertEquals(['foo' => 'bar'], $globals['get']);
        $this->assertEquals(['note' => 'somenote'], $globals['post']);
        $this->assertEquals(['foo' => 'bar', 'note' => 'somenote'], $globals['request']);
        $this->assertEquals(['upfile' => ['penguim.png', 'image/png', $png, UPLOAD_ERR_OK, strlen($png)]], $globals['files']);

        // Each upload had its own temporary file, not named after the client's file, removed after
        $this->assertStringNotContainsString('penguim', $globals['tmp_names'][0]);
        $this->assertFileDoesNotExist($globals['tmp_names'][0]);
    }

    /**
     * A GET with a query string and headers: the request and $_GET have the query string (it was lost before
     * 7.0), the headers are in the server params, and a JSON content type with no body gives no POST data
     */
    public function testQueryStringAndHeadersOfAGet(): void
    {
        $request = \ByJG\WebRequest\Psr7\Request::getInstance(new Uri('http://localhost/list?page=5&size=100&orderBy=id'))
            ->withMethod('GET')
            ->withHeader('Authorization', 'Bearer abc')
            ->withHeader('Content-Type', 'application/json');
        $routes = (new RouteList())->setRoutes([
            Route::get('/list')->withOutputProcessor(JsonOutputProcessor::class)->withClosure(
                function (HttpResponse $response, HttpRequest $request) {
                    $this->seen = [
                        'query' => $request->query(),
                        'authorization' => $request->server('HTTP_AUTHORIZATION'),
                        'content_type' => $request->server('CONTENT_TYPE'),
                        'body' => $request->body(),
                        'payload' => $request->payload(),
                        'get' => $_GET,
                        'post' => $_POST,
                    ];
                    $response->write(['ok' => true]);
                }
            ),
        ]);

        MockServer::mock($routes, $request);

        $this->assertEquals(['page' => '5', 'size' => '100', 'orderBy' => 'id'], $this->seen['query']);
        $this->assertEquals(['page' => '5', 'size' => '100', 'orderBy' => 'id'], $this->seen['get']);
        $this->assertEquals('Bearer abc', $this->seen['authorization']);
        $this->assertEquals('application/json', $this->seen['content_type']);
        $this->assertEquals([], $this->seen['body']);
        $this->assertEquals('', $this->seen['payload']);
        $this->assertEquals([], $this->seen['post']);
    }

    /**
     * A query given in the path of the URI (withPath('/list?page=2'), as php-swagger-test sends it) is the query
     */
    public function testQueryStringGivenInThePath(): void
    {
        $request = \ByJG\WebRequest\Psr7\Request::getInstance((new Uri('http://localhost/'))->withPath('/list?page=2&size=10'))
            ->withMethod('GET');
        $routes = (new RouteList())->setRoutes([
            Route::get('/list')->withOutputProcessor(JsonOutputProcessor::class)->withClosure(
                function (HttpResponse $response, HttpRequest $request) {
                    $this->seen = ['query' => $request->query(), 'get' => $_GET];
                    $response->write(['ok' => true]);
                }
            ),
        ]);

        MockServer::mock($routes, $request);

        $this->assertEquals(['page' => '2', 'size' => '10'], $this->seen['query']);
        $this->assertEquals(['page' => '2', 'size' => '10'], $this->seen['get']);
    }

    public function testFormUrlEncodedAndCookie(): void
    {
        $request = RequestFormUrlEncoded::build(new Uri('http://localhost/form?foo=bar'), ['bar' => 'foo', 'test' => 'ok'])
            ->withHeader('Cookie', 'name=joao; year=1974');

        MockServer::mock($this->routes(), $request);

        $this->assertEquals(['bar' => 'foo', 'test' => 'ok'], $this->seen['body']);
        $this->assertEquals(['name' => 'joao', 'year' => '1974'], $this->seen['cookie']);
        $this->assertEquals([], $this->seen['files']);
        $this->assertEquals(['bar' => 'foo', 'test' => 'ok'], $this->seen['globals']['post']);
        $this->assertEquals(['foo' => 'bar', 'bar' => 'foo', 'test' => 'ok'], $this->seen['globals']['request']);
        $this->assertEquals(['name' => 'joao', 'year' => '1974'], $this->seen['globals']['cookie']);
        $this->assertEquals([], $this->seen['globals']['files']);
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function serverPortProvider(): array
    {
        return [
            'http' => ['http://localhost/form', 80],
            'http on 80' => ['http://localhost:80/form', 80],
            'http on another port' => ['http://localhost:8080/form', 8080],
            'https' => ['https://localhost/form', 443],
            'https on 443' => ['https://localhost:443/form', 443],
        ];
    }

    /**
     * SERVER_PORT is always set, as a web server sets it: the URI leaves out the standard port of the scheme
     */
    #[DataProvider('serverPortProvider')]
    public function testServerPort(string $uri, int $port): void
    {
        MockServer::mock($this->routes(), RequestFormUrlEncoded::build(new Uri($uri), ['bar' => 'foo']));

        $this->assertSame($port, $this->seen['globals']['server_port']);
    }

    public function testTheGlobalsAreRestoredAfterTheRequest(): void
    {
        $request = RequestFormUrlEncoded::build(new Uri('http://localhost/form'), ['bar' => 'foo'])
            ->withHeader('Cookie', 'name=joao');

        MockServer::mock($this->routes(), $request);

        $this->assertEquals(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/before'], $_SERVER);
        $this->assertEquals(['before' => '1'], $_GET);
        $this->assertEquals([], $_POST);
        $this->assertEquals([], $_REQUEST);
        $this->assertEquals([], $_COOKIE);
        $this->assertEquals([], $_FILES);
    }

    public function testThePsr7ResponseHasTheHeadersByName(): void
    {
        $routes = (new RouteList())->setRoutes([
            Route::get('/headers')->withOutputProcessor(JsonOutputProcessor::class)->withClosure(
                function (HttpResponse $response, HttpRequest $request): void {
                    $response->addHeader('Location', 'http://localhost:8080/next');
                    $response->addHeader('X-Values', ['a', 'b']);
                    $response->write(['ok' => true]);
                }
            ),
        ]);

        $response = MockServer::mock($routes, \ByJG\WebRequest\Psr7\Request::getInstance(new Uri('http://localhost/headers')))
            ->getPsr7Response();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        // A value with its own colons is kept whole
        $this->assertEquals('http://localhost:8080/next', $response->getHeaderLine('Location'));
        // A header sent more than once keeps every value
        $this->assertEquals(['a', 'b'], $response->getHeader('X-Values'));
        // The status line is not a header, and no header is named by its position
        $this->assertFalse($response->hasHeader('0'));
        $this->assertFalse($response->hasHeader('HTTP/1.1 200 OK'));
    }
}
