<?php

namespace Tests;

use ByJG\RestServer\Exception\Error404Exception;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\RoutePattern;
use ByJG\RestServer\ServerRequestHandler;
use PHPUnit\Framework\TestCase;

require __DIR__ . '/AssertHandler.php';
require __DIR__ . '/ServerRequestHandlerExposed.php';

define("RESTSERVER_TEST", "RESTSERVER_TEST");

class ServerRequestHandlerTest extends TestCase
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
        $_SERVER['SCRIPT_FILENAME'] = null;

        $this->object->handle(null, false, false);
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

        $this->assertTrue($this->object->handle(null, false, false));
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
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "file://" . __DIR__ . "/mimefiles/test.php";
        $_SERVER['SCRIPT_FILENAME'] = __DIR__ . "/mimefiles/test.php";

        $this->assertTrue($this->object->handle(null, false, false));
    }

    public function testSortPaths()
    {
        // Expose the method
        $testObject = new ServerRequestHandlerExposed();

        $pathList = [
            "/rest/accessible/recentPosts",
            "/rest/accessible/postsWithFilter",
            "/rest/audio/{id}",
            "/rest/audio/all",
            "/rest/audio",
            "/rest/audio/upload",
            "/rest/backgroundaudio/{id}",
            "/rest/backgroundaudio/all",
            "/rest/backgroundaudio",
            "/rest/blog/{id}",
            "/rest/blog/all",
            "/rest/blog",
            "/rest/dictionary/{id}",
            "/rest/dictionary/all",
            "/rest/dictionary",
            "/rest/registerblog/tts",
            "/rest/registerblog/availlang",
            "/rest/registerblog/platforms",
            "/rest/registerblog/sanitizewpurl",
            "/rest/registerblog/checkplugin",
            "/rest/registerblog/checkfeed",
            "/rest/login",
            "/rest/narrator/{id}",
            "/rest/narrator/{id:unique}",
            "/rest/narrator/all",
            "/rest/narrator",
            "/rest/newsletter/email",
            "/rest/platform/{id}",
            "/rest/platform/all",
            "/rest/platform",
            "/rest/post/{id}",
            "/rest/post/all",
            "/rest/post",
            "/rest/post/activeaudio/{id}",
            "/rest/audiowidget/{objectid}",
            "/rest/audiowidget/blog",
            "/rest/audiowidget/send",
            "/rest/audiowidget/post/{blogId}",
            "/rest/audiowidget/notify/{blogId}/{event}",
            "/rest/logplayer",
        ];

        $pathResult = $testObject->sortPaths($pathList);

        $this->assertEquals(
            [
                "/rest/accessible/postsWithFilter",
                "/rest/accessible/recentPosts",
                "/rest/audio/all",
                "/rest/audio/upload",
                "/rest/audiowidget/blog",
                "/rest/audiowidget/send",
                "/rest/audio",
                "/rest/backgroundaudio/all",
                "/rest/backgroundaudio",
                "/rest/blog/all",
                "/rest/blog",
                "/rest/dictionary/all",
                "/rest/dictionary",
                "/rest/login",
                "/rest/logplayer",
                "/rest/narrator/all",
                "/rest/narrator",
                "/rest/newsletter/email",
                "/rest/platform/all",
                "/rest/platform",
                "/rest/post/all",
                "/rest/post",
                "/rest/registerblog/availlang",
                "/rest/registerblog/checkfeed",
                "/rest/registerblog/checkplugin",
                "/rest/registerblog/platforms",
                "/rest/registerblog/sanitizewpurl",
                "/rest/registerblog/tts",
                "/rest/audio/{id}",
                "/rest/audiowidget/notify/{blogId}/{event}",
                "/rest/audiowidget/post/{blogId}",
                "/rest/audiowidget/{objectid}",
                "/rest/backgroundaudio/{id}",
                "/rest/blog/{id}",
                "/rest/dictionary/{id}",
                '/rest/narrator/{id:unique}',
                "/rest/narrator/{id}",
                "/rest/platform/{id}",
                "/rest/post/activeaudio/{id}",
                "/rest/post/{id}",
            ],
            $pathResult
        );
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
