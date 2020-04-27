<?php

namespace Tests;

use ByJG\RestServer\MockHttpRequest;
use ByJG\Util\Helper\RequestFormUrlEncoded;
use ByJG\Util\Helper\RequestMultiPart;
use ByJG\Util\MultiPartItem;
use ByJG\Util\Uri;
use PHPUnit\Framework\TestCase;

class MockHttpRequestTest extends TestCase
{
    public function testInitializePHPFile()
    {
        $multiPartItems = [
            new MultiPartItem("note", "somenote"),
            new MultiPartItem("upfile", file_get_contents(__DIR__ . "/mimefiles/test.png"), "penguim.png", "image/png")
        ];

        $psr7Request = RequestMultiPart::build(new Uri("/?foo=bar"), "POST", $multiPartItems);
        $mockHttpRequest = new MockHttpRequest($psr7Request);

        $this->assertEquals(
            [
                "upfile" =>
                    [
                        'name' => 'upfile',
                        'type' => 'image/png',
                        'size' => 19187,
                        'tmp_name' => '/tmp/penguim.png',
                        'error' => null,
                    ]
            ],
            $_FILES
        );

        $this->assertEquals(
            [
                "note" => "somenote\n"
            ],
            $_POST
        );

        $this->assertEquals(
            [
                "foo" => "bar"
            ],
            $_GET
        );

        $this->assertEquals([], $_COOKIE);

        $this->assertEquals(
            [
                "note" => "somenote\n",
                "foo" => "bar"
            ],
            $_REQUEST
        );
    }

    public function testFormUrlEncoded()
    {
        $multiPartItems = [
            new MultiPartItem("note", "somenote"),
            new MultiPartItem("upfile", file_get_contents(__DIR__ . "/mimefiles/test.png"), "penguim.png", "image/png")
        ];

        $psr7Request = RequestFormUrlEncoded::build(new Uri("/?foo=bar"), ["bar" => "foo", "test"=>"ok"]);
        $mockHttpRequest = new MockHttpRequest($psr7Request);

        $this->assertEquals([], $_FILES);

        $this->assertEquals(
            [
                "bar" => "foo",
                "test" => "ok"
            ],
            $_POST
        );

        $this->assertEquals(
            [
                "foo" => "bar"
            ],
            $_GET
        );

        $this->assertEquals([], $_COOKIE);

        $this->assertEquals(
            [
                "bar" => "foo",
                "foo" => "bar",
                "test" => "ok"
            ],
            $_REQUEST
        );
    }

    public function testFormUrlEncodedAndCookie()
    {
        $multiPartItems = [
            new MultiPartItem("note", "somenote"),
            new MultiPartItem("upfile", file_get_contents(__DIR__ . "/mimefiles/test.png"), "penguim.png", "image/png")
        ];

        $psr7Request = RequestFormUrlEncoded::build(new Uri("/?foo=bar"), ["bar" => "foo", "test"=>"ok"])
            ->withHeader("Cookie", "name=joao; year=1974");

        $mockHttpRequest = new MockHttpRequest($psr7Request);

        $this->assertEquals([], $_FILES);

        $this->assertEquals(
            [
                "bar" => "foo",
                "test" => "ok"
            ],
            $_POST
        );

        $this->assertEquals(
            [
                "foo" => "bar"
            ],
            $_GET
        );

        $this->assertEquals(
            [
                "name" => "joao",
                "year" => 1974
            ],
            $_COOKIE
        );

        $this->assertEquals(
            [
                "bar" => "foo",
                "foo" => "bar",
                "test" => "ok"
            ],
            $_REQUEST
        );
    }

}
