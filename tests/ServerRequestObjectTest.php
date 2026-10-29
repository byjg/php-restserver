<?php

namespace Tests;

use ByJG\RestServer\ErrorHandler;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\OutputProcessor\JsonOutputProcessor;
use ByJG\RestServer\Route\Route;
use ByJG\RestServer\Route\RouteList;
use ByJG\RestServer\Server;
use ByJG\RestServer\Writer\MemoryWriter;
use ByJG\Util\Uri;
use ByJG\WebRequest\Psr7\MemoryStream;
use ByJG\WebRequest\Psr7\ServerRequest;
use Override;
use PHPUnit\Framework\TestCase;

/**
 * Server::withRequestObject(): the Server handles a PSR-7 request instead of PHP's globals,
 * as a PSR-7 runtime or a persistent worker (where php://input is not the body) needs.
 */
class ServerRequestObjectTest extends TestCase
{
    #[Override]
    public function tearDown(): void
    {
        $_SERVER = [];
        ErrorHandler::getInstance()->unregister();
    }

    public function testHandlesThePsr7RequestInsteadOfTheGlobals(): void
    {
        // The globals describe another request
        $_SERVER = ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/other', 'REMOTE_ADDR' => '192.168.0.1'];
        $_GET = ['page' => 'from-globals'];

        $routes = (new RouteList())->addRoute(Route::post('/echo')->withClosure(
            function (HttpResponse $response, HttpRequest $request) {
                $response->write([
                    'payload' => json_decode($request->payload(), true),
                    'page' => $request->query('page'),
                    'ip' => $request->getRequestIp(),
                    'authorization' => $request->header('Authorization'),
                ]);
            }
        ));

        $request = (new ServerRequest(
            Uri::getInstance('http://localhost/echo?page=2'),
            ['REMOTE_ADDR' => '10.0.0.5']
        ))
            ->withMethod('POST')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Authorization', 'Bearer token123')
            ->withBody(new MemoryStream('{"name":"John"}'));

        $writer = new MemoryWriter();
        (new Server())
            ->withDefaultOutputProcessor(JsonOutputProcessor::class)
            ->withWriter($writer)
            ->withRequestObject($request)
            ->handle($routes, false);

        $this->assertEquals(200, $writer->getStatusCode());
        $this->assertEquals(
            ['payload' => ['name' => 'John'], 'page' => '2', 'ip' => '10.0.0.5', 'authorization' => 'Bearer token123'],
            json_decode($writer->getData(), true)
        );
    }

    /**
     * A Server can serve many requests, as a DI singleton does in a persistent worker: the
     * response of one request must not leak into the next.
     */
    public function testTheSameServerHandlesConsecutiveRequests(): void
    {
        $routes = (new RouteList())->addRoute(Route::post('/echo')->withClosure(
            function (HttpResponse $response, HttpRequest $request) {
                $response->write(['payload' => $request->payload()]);
            }
        ));
        $server = (new Server())->withDefaultOutputProcessor(JsonOutputProcessor::class);

        $outputs = [];
        foreach (['first', 'second'] as $payload) {
            $writer = new MemoryWriter();
            $server
                ->withWriter($writer)
                ->withRequestObject(
                    (new ServerRequest(Uri::getInstance('http://localhost/echo'), ['REMOTE_ADDR' => '10.0.0.5']))
                        ->withMethod('POST')
                        ->withBody(new MemoryStream($payload))
                )
                ->handle($routes, false);
            $outputs[] = json_decode($writer->getData(), true);
        }

        $this->assertEquals([['payload' => 'first'], ['payload' => 'second']], $outputs);
    }
}
