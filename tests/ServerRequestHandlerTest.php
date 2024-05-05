<?php

namespace Tests;

use ByJG\RestServer\Exception\ClassNotFoundException;
use ByJG\RestServer\Exception\Error404Exception;
use ByJG\RestServer\Exception\Error405Exception;
use PHPUnit\Framework\TestCase;

class ServerRequestHandlerTest extends TestCase
{
    use MockServerTrait;

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
}
