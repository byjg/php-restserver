<?php

namespace Tests;

use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpRequestHandler;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\Middleware\JwtMiddleware;
use ByJG\RestServer\OutputProcessor\JsonOutputProcessor;
use ByJG\RestServer\Route\Route;
use ByJG\RestServer\Route\RouteList;
use ByJG\RestServer\Writer\MemoryWriter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

trait MockServerTrait
{
    /**
     * @var HttpRequestHandler
     */
    protected $object;

    /**
     * @var RouteList
     */
    protected $definition;

    protected $reach = false;

    public $headers = null;

    public function setup(): void
    {
        ini_set('output_buffering', 4096);

        $logger = new Logger("unittest");
        $stream_handler = new StreamHandler("php://stderr");
        $logger->pushHandler($stream_handler);
        $this->object = new HttpRequestHandler($logger);

        $this->reach = false;

        $this->definition = new RouteList();

        $this->definition->addRoute(
            Route::get('/test')
                ->withClosure(function ($response, $request) {
                    $this->assertInstanceOf(HttpResponse::class, $response);
                    $this->assertInstanceOf(HttpRequest::class, $request);
                    $response->write(["key" => "value"]);
                    $this->reach = true;
                })
        );

        $this->definition->addRoute(
            Route::get('/test/{id}')
                ->withClosure(function ($response, $request) {
                    $this->assertInstanceOf(HttpResponse::class, $response);
                    $this->assertInstanceOf(HttpRequest::class, $request);
                    $this->reach = $request->param('id');
                    $response->write(["key" => $request->param('id')]);
                })
        );

        $this->definition->addRoute(
            Route::get('/corstest/{id}')
                ->withClosure(function ($response, $request) {
                    $this->assertInstanceOf(HttpResponse::class, $response);
                    $this->assertInstanceOf(HttpRequest::class, $request);
                    $this->reach = $request->param('id');
                    $response->write("Success!");
                })
        );

        $this->definition->addRoute(
            Route::get("/testjwt")
                ->withClosure(function ($response, $request) {
                    $response->write(
                        [
                            JwtMiddleware::JWT_PARAM_PARSE_STATUS => $request->param(JwtMiddleware::JWT_PARAM_PARSE_STATUS),
                            JwtMiddleware::JWT_PARAM_PARSE_MESSAGE => $request->param(JwtMiddleware::JWT_PARAM_PARSE_MESSAGE),
                            JwtMiddleware::JWT_PARAM_PREFIX . ".userid" => $request->param(JwtMiddleware::JWT_PARAM_PREFIX . ".userid")
                        ]
                    );
                })
        );

        $this->definition->addRoute(
            (new Route('GET', '/error'))
                ->withClass('\\My\\Class', 'method')
        );
    }

    public function tearDown(): void
    {
        $this->object = null;
        $this->reach = false;
        $this->headers = null;
        $_SERVER = [];
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        $_COOKIE = [];
        $_FILES = [];
    }

    public function processAndGetContent($handler, $expectedHeader, $expectedData, $middleWare = null, $expectedParams = [])
    {
        $writer = new MemoryWriter();

        try {
            $handler
                ->withDefaultOutputProcessor(JsonOutputProcessor::class)
                ->withWriter($writer);

            if (!is_null($middleWare)) {
                $handler->withMiddleware($middleWare);
            }

            $handler->handle($this->definition, true, false);
        } finally {
            $result = ob_get_contents();
            ob_clean();
            ob_end_flush();
            $this->assertEmpty($result);
            if (!is_null($expectedHeader)) {
                $this->assertEquals($expectedHeader, $writer->getHeaders());
            }
            $this->assertEquals($expectedData, $writer->getData());
        }
    }
}