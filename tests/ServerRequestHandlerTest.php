<?php

namespace Tests;

use ByJG\RestServer\Exception\ClassNotFoundException;
use ByJG\RestServer\Exception\Error401Exception;
use ByJG\RestServer\Exception\Error404Exception;
use ByJG\RestServer\Exception\Error405Exception;
use ByJG\RestServer\Exception\Error415Exception;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\Route\RouteList;
use ByJG\RestServer\HttpRequestHandler;
use ByJG\RestServer\Middleware\CorsMiddleware;
use ByJG\RestServer\Middleware\ServerStaticMiddleware;
use ByJG\RestServer\OutputProcessor\JsonOutputProcessor;
use ByJG\RestServer\Route\Route;
use ByJG\RestServer\Writer\MemoryWriter;
use PHPUnit\Framework\TestCase;

require __DIR__ . '/OpenApiWrapperExposed.php';

define("RESTSERVER_TEST", "RESTSERVER_TEST");

class ServerRequestHandlerTest extends TestCase
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
        $this->object = new HttpRequestHandler();
        
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
            (new Route('GET', '/error'))
                ->withClass('\\My\\Class', 'method')
        );
    }

    public function tearDown(): void
    {
        $this->object = null;
        $this->reach = false;
        $this->headers = null;
    }

    /**
     * @throws \ByJG\RestServer\Exception\ClassNotFoundException
     * @throws \ByJG\RestServer\Exception\Error404Exception
     * @throws \ByJG\RestServer\Exception\Error405Exception
     * @throws \ByJG\RestServer\Exception\Error520Exception
     * @throws \ByJG\RestServer\Exception\InvalidClassException
     */
    public function testHandle1()
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
     * @throws \ByJG\RestServer\Exception\ClassNotFoundException
     * @throws \ByJG\RestServer\Exception\Error404Exception
     * @throws \ByJG\RestServer\Exception\Error405Exception
     * @throws \ByJG\RestServer\Exception\Error520Exception
     * @throws \ByJG\RestServer\Exception\InvalidClassException
     */
    public function testHandle2()
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

    /**
     * @throws \ByJG\RestServer\Exception\ClassNotFoundException
     * @throws \ByJG\RestServer\Exception\Error404Exception
     * @throws \ByJG\RestServer\Exception\Error405Exception
     * @throws \ByJG\RestServer\Exception\Error520Exception
     * @throws \ByJG\RestServer\Exception\InvalidClassException
     */
    public function testHandle3()
    {
        $this->expectException(Error405Exception::class);
        $expectedData = '[]';

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = "http://localhost/test";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        $this->processAndGetContent($this->object, null, $expectedData);
    }

    /**
     * @throws \ByJG\RestServer\Exception\ClassNotFoundException
     * @throws \ByJG\RestServer\Exception\Error404Exception
     * @throws \ByJG\RestServer\Exception\Error405Exception
     * @throws \ByJG\RestServer\Exception\Error520Exception
     * @throws \ByJG\RestServer\Exception\InvalidClassException
     */
    public function testHandle4()
    {
        $this->expectException(Error404Exception::class);
        $expectedData = '[]';

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "http://localhost/doesnotexists";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        $this->processAndGetContent($this->object, null, $expectedData);
    }

    /**
     * @throws \ByJG\RestServer\Exception\ClassNotFoundException
     * @throws \ByJG\RestServer\Exception\Error404Exception
     * @throws \ByJG\RestServer\Exception\Error405Exception
     * @throws \ByJG\RestServer\Exception\Error520Exception
     * @throws \ByJG\RestServer\Exception\InvalidClassException
     */
    public function testHandle5()
    {
        $this->expectException(ClassNotFoundException::class);
        $expectedData = '';

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "http://localhost/error";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        $this->processAndGetContent($this->object, null, $expectedData);
    }

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
            "Content-Type: application/json; charset=us-ascii",
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

    public function testHandleCors()
    {
        $expectedHeader = [
            "HTTP/1.1 200 OK",
            "Content-Type: application/json",
            "Access-Control-Allow-Origin: http://localhost",
            "Access-Control-Allow-Credentials: true",
            "Access-Control-Max-Age: 86400",
        ];
        $expectedData = "[\"Success!\"]";

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "http://localhost/corstest/tCors";
        $_SERVER['HTTP_ORIGIN'] = "http://localhost";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        $this->processAndGetContent($this->object, $expectedHeader, $expectedData, new CorsMiddleware());

        $this->assertEquals('tCors', $this->reach);


    }

    public function testHandleCorsOptions()
    {
        $expectedHeader = [
            "HTTP/1.1 200 OK",
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

    public function testFailedCorsWrongAllowedServer()
    {
        $this->expectException(Error401Exception::class);
        $this->expectExceptionMessage("CORS verification failed. Request Blocked.");

        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
        $_SERVER['REQUEST_URI'] = "http://localhost/corstest/tCors";
        $_SERVER['HTTP_ORIGIN'] = "http://localhost";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        $this->processAndGetContent($this->object, null, '[]', (new CorsMiddleware())->withCorsOrigins("anotherhost"));
    }

    public function testDefaultCorsSetup()
    {
        $expectedHeader = [
            "HTTP/1.1 200 OK",
            "Content-Type: application/json",
            "Access-Control-Allow-Origin: http://anyhostisallowed",
            "Access-Control-Allow-Credentials: true",
            "Access-Control-Max-Age: 86400",
        ];
        $expectedData = "[\"Success!\"]";

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "http://localhost/corstest/tCors";
        $_SERVER['HTTP_ORIGIN'] = "http://anyhostisallowed";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        $this->processAndGetContent($this->object, $expectedHeader, $expectedData, new CorsMiddleware());

        $this->assertEquals('tCors', $this->reach);
    }

    public function testCorsDisabled()
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


    public function mimeDataProvider()
    {
        return [
            [ __DIR__ . "/mimefiles/test.json", "application/json; charset=us-ascii"],
            [ __DIR__ . "/mimefiles/test.pdf", "application/pdf; charset=binary"],
            [ __DIR__ . "/mimefiles/test.png", "image/png; charset=binary"],
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

    public function processAndGetContent($handler, $expectedHeader, $expectedData, $middleWare = null)
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
