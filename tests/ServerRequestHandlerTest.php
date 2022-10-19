<?php

namespace Tests;

use ByJG\RestServer\Exception\ClassNotFoundException;
use ByJG\RestServer\Exception\Error400Exception;
use ByJG\RestServer\Exception\Error404Exception;
use ByJG\RestServer\Exception\Error405Exception;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\Route\RouteDefinition;
use ByJG\RestServer\HttpRequestHandler;
use ByJG\RestServer\OutputProcessor\JsonOutputProcessor;
use ByJG\RestServer\OutputProcessor\MockOutputProcessor;
use ByJG\RestServer\Route\RoutePattern;
use PHPUnit\Framework\TestCase;

require __DIR__ . '/AssertOutputProcessor.php';
require __DIR__ . '/HttpRequestHandlerExposed.php';
require __DIR__ . '/OpenApiWrapperExposed.php';

define("RESTSERVER_TEST", "RESTSERVER_TEST");

class ServerRequestHandlerTest extends TestCase
{
    /**
     * @var HttpRequestHandler
     */
    protected $object;

    /**
     * @var RouteDefinition
     */
    protected $definition;

    protected $reach = false;

    public $headers = null;

    public function setup(): void
    {
        $this->object = new HttpRequestHandlerExposed();
        
        $this->reach = false;
        
        $this->definition = new RouteDefinition();
        
        $this->definition->addRoute(
            RoutePattern::get(
                '/test',
                AssertOutputProcessor::class,
                function ($response, $request) {
                    $this->assertInstanceOf(HttpResponse::class, $response);
                    $this->assertInstanceOf(HttpRequest::class, $request);
                    $this->reach = true;
                }
            )
        );

        $this->definition->addRoute(
            RoutePattern::get(
                '/test/{id}',
                AssertOutputProcessor::class,
                function ($response, $request) {
                    $this->assertInstanceOf(HttpResponse::class, $response);
                    $this->assertInstanceOf(HttpRequest::class, $request);
                    $this->reach = $request->param('id');
                }
            )
        );

        $this->definition->addRoute(
            RoutePattern::get(
                '/corstest/{id}',
                function () {
                    return new AssertOutputProcessor(true);
                },
                function ($response, $request) {
                    $this->assertInstanceOf(HttpResponse::class, $response);
                    $this->assertInstanceOf(HttpRequest::class, $request);
                    $this->reach = $request->param('id');
                    $response->write("Enter");
                }
            )
        );

        $this->definition->addRoute(
            new RoutePattern(
                'GET',
                '/error',
                AssertOutputProcessor::class,
                '\\My\\Class',
                'method'
            )
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
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "http://localhost/test";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        $this->object->handle($this->definition, false, false);
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
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "http://localhost/test/45";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        $this->object->handle($this->definition, false, false);
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

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = "http://localhost/test";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        $this->object->handle($this->definition, false, false);
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

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "http://localhost/doesnotexists";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        $this->object->handle($this->definition, false, false);
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

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "http://localhost/error";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        $this->object->handle($this->definition, false, false);
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
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "file://" . __DIR__ . "/mimefiles/test.json";
        $_SERVER['SCRIPT_FILENAME'] = __DIR__ . "/mimefiles/test.json";

        $this->assertTrue($this->object->handle($this->definition, false, false));
        $this->assertTrue($this->object->tryDeliveryPhysicalFile());
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
        $this->expectException(Error404Exception::class);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "file://" . __DIR__ . "/mimefiles/test.php";
        $_SERVER['SCRIPT_FILENAME'] = __DIR__ . "/mimefiles/test.php";

        $this->assertTrue($this->object->handle($this->definition, false, false));
    }

    public function testHandleCors()
    {
        $expected = [
            "HTTP/1.1 200",
            "Content-Type: application/json",
            "Access-Control-Allow-Origin: http://localhost",
            "Access-Control-Allow-Credentials: true",
            "Access-Control-Max-Age: 86400",
            "",
            "[\"Enter\"]"
        ];

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "http://localhost/corstest/tCors";
        $_SERVER['HTTP_ORIGIN'] = "http://localhost";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        $this->object->withCorsOrigins("localhost")
            ->withDefaultOutputProcessor(function () {
                return new MockOutputProcessor(JsonOutputProcessor::class);
            });
            $this->object->handle($this->definition, true, false);

        $result = ob_get_contents();
        ob_clean();

        $this->assertEquals('tCors', $this->reach);

        $this->assertEquals(implode("\r\n", $expected), $result);

    }

    public function testHandleCorsOptions()
    {
        $expected = [
            "HTTP/1.1 200",
            "Content-Type: application/json",
            "Access-Control-Allow-Origin: http://localhost",
            "Access-Control-Allow-Credentials: true",
            "Access-Control-Max-Age: 86400",
            "Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE,PATCH",
            "Access-Control-Allow-Headers: Authorization,Content-Type,Accept,Origin,User-Agent,Cache-Control,Keep-Alive,X-Requested-With,If-Modified-Since",
            "",
            "[]"
        ];

        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
        $_SERVER['REQUEST_URI'] = "http://localhost/corstest/tCors";
        $_SERVER['HTTP_ORIGIN'] = "http://localhost";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        $this->object->withCorsOrigins(["server\.com", "localhost"])
            ->withDefaultOutputProcessor(function () {
                return new MockOutputProcessor(JsonOutputProcessor::class);
            })
            ->handle($this->definition, true, false);

        $result = ob_get_contents();
        ob_clean();

        $this->assertEquals(implode("\r\n", $expected), $result);

        // $this->assertEquals($a, $b);
        // $this->assertEquals('tCors', $this->reach);

    }

    public function testFailedCorsValidation()
    {
        $expected = [
            "HTTP/1.1 200",
            "Content-Type: application/json",
            "",
            "[]"
        ];

        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
        $_SERVER['REQUEST_URI'] = "http://localhost/corstest/tCors";
        $_SERVER['HTTP_ORIGIN'] = "http://localhost";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        $this->object->withCorsOrigins("anotherhost")
            ->withDefaultOutputProcessor(function () {
                return new MockOutputProcessor(JsonOutputProcessor::class);
            })
            ->handle($this->definition, true, false);

        $result = ob_get_contents();
        ob_clean();

        $this->assertEquals(implode("\r\n", $expected), $result);

        // $this->assertEquals($a, $b);
        // $this->assertEquals('tCors', $this->reach);

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
        $this->assertEquals($expected, $this->object->mimeContentType($entry));
    }

    public function testMimeContentTypeNotFound()
    {
        $this->expectException(Error404Exception::class);

        $this->assertEquals("", $this->object->mimeContentType("test/aaaa"));
    }
}
