<?php

namespace Tests;

use ByJG\RestServer\Exception\Error404Exception;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\Route\RouteDefinition;
use ByJG\RestServer\HttpRequestHandler;
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

    public function setUp()
    {
        $this->object = new HttpRequestHandlerExposed();
        
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
            new RoutePattern(
                'GET',
                '/error',
                AssertOutputProcessor::class,
                '\\My\\Class',
                'method'
            )
        );
    }

    public function tearDown()
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
     * @expectedException \ByJG\RestServer\Exception\Error405Exception
     */
    public function testHandle3()
    {
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
     * @expectedException \ByJG\RestServer\Exception\Error404Exception
     */
    public function testHandle4()
    {
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
     * @expectedException \ByJG\RestServer\Exception\ClassNotFoundException
     */
    public function testHandle5()
    {
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
     * @expectedException \ByJG\RestServer\Exception\Error404Exception
     */
    public function testHandle7()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "file://" . __DIR__ . "/mimefiles/test.php";
        $_SERVER['SCRIPT_FILENAME'] = __DIR__ . "/mimefiles/test.php";

        $this->assertTrue($this->object->handle($this->definition, false, false));
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

    /**
     * @expectedException \ByJG\RestServer\Exception\Error404Exception
     */
    public function testMimeContentTypeNotFound()
    {
        $this->assertEquals("", $this->object->mimeContentType("test/aaaa"));
    }
}
