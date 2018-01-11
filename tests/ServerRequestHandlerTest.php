<?php

namespace Tests;

use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\RoutePattern;
use ByJG\RestServer\ServerRequestHandler;

require __DIR__ . '/AssertHandler.php';

// backward compatibility
if (!class_exists('\PHPUnit\Framework\TestCase')) {
    class_alias('\PHPUnit_Framework_TestCase', '\PHPUnit\Framework\TestCase');
}

class ServerRequestHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ServerRequestHandler
     */
    protected $object;

    protected $reach = false;

    public $headers = null;

    public function setUp()
    {
        $this->object = new ServerRequestHandler();
        $this->object->setDefaultHandler(new AssertHandler());

        $this->object->addRoute(
            RoutePattern::get(
                '/test',
                function ($response, $request) {
                    $this->assertInstanceOf(HttpResponse::class, $response);
                    $this->assertInstanceOf(HttpRequest::class, $request);
                    $this->reach = true;
                }
            )
        );

        $this->object->addRoute(
            RoutePattern::get(
                '/test/{id}',
                function ($response, $request) {
                    $this->assertInstanceOf(HttpResponse::class, $response);
                    $this->assertInstanceOf(HttpRequest::class, $request);
                    $this->reach = $request->get('id');
                }
            )
        );

        $this->object->addRoute(
            new RoutePattern(
                'GET',
                '/error',
                AssertHandler::class,
                'method',
                '\\My\\Class'
            )
        );
    }

    public function tearDown()
    {
        $this->object = null;
        $this->reach = false;
        $this->headers = null;
    }

    public function testHandle1()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "http://localhost/test";
        $_SERVER['SCRIPT_FILENAME'] = null;

        $this->object->handle(null, false, false);
        $this->assertTrue($this->reach);
    }

    public function testHandle2()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "http://localhost/test/45";
        $_SERVER['SCRIPT_FILENAME'] = null;

        $this->object->handle(null, false, false);
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
        $_SERVER['SCRIPT_FILENAME'] = null;

        $this->object->handle(null, false, false);
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
        $_SERVER['SCRIPT_FILENAME'] = null;

        $this->object->handle(null, false, false);
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
        $_SERVER['SCRIPT_FILENAME'] = null;

        $this->object->handle(null, false, false);
    }
}
