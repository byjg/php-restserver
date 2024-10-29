<?php

namespace Tests;

use ByJG\RestServer\HttpRequest;
use PHPUnit\Framework\TestCase;

class HttpRequestTest extends TestCase
{
    /**
     * @var HttpRequest $request
     */
    protected $request;

    protected function setUp(): void
    {
        $_FILES = [];
        $this->request = new HttpRequest(
            ['getp' => 1],
            ['postp' => 2],
            [
                'HTTP_CONTENT_TYPE' => 'application/json',
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/test/123?a=1',
            ],
            ['sessionp' => 4],
            ['cookiep' => 5],
            ['paramp' => 6]
        );
    }

    public function testUploadedFiles(): void
    {
        $_FILES = [
            'test' => [
                'name' => 'test.txt',
                'type' => 'text/plain',
                'size' => 4,
                'tmp_name' => sys_get_temp_dir() . "/tmpfile.txt",
                'error' => UPLOAD_ERR_OK
            ]
        ];
        file_put_contents($_FILES['test']['tmp_name'], 'test');

        $upload = $this->request->uploadedFiles();
        $this->assertEquals(1, $upload->count());
        $this->assertEquals(['test'], $upload->getKeys());
        $this->assertTrue($upload->isOk('test'));
        $this->assertSame(UPLOAD_ERR_OK, $upload->getErrorCode('test'));
        $this->assertEquals('test.txt', $upload->getFileName('test'));
        $this->assertEquals('text/plain', $upload->getFileType('test'));
        $this->assertEquals(4, $upload->getFileSize('test'));
        $this->assertEquals('test', $upload->getUploadedFile('test'));
        $upload->clearTemp('test');
    }

    public function testCookie(): void
    {
        $this->assertEquals(5, $this->request->cookie('cookiep'));
        $this->assertEquals(5, $this->request->request('cookiep'));
        $this->assertEquals(['cookiep' => 5], $this->request->cookie());
    }

    public function testSession(): void
    {
        $this->assertEquals(4, $this->request->session('sessionp'));
        $this->assertEquals(4, $this->request->request('sessionp'));
        $this->assertEquals(['sessionp' => 4], $this->request->session());
    }

    public function testParam(): void
    {
        $this->assertEquals(6, $this->request->param('paramp'));
        $this->assertEmpty($this->request->request('paramp'));
        $this->assertEquals(['paramp' => 6], $this->request->param());
    }

    public function testGetHeader(): void
    {
        $this->assertEquals('application/json', $this->request->getHeader('content-type'));
    }

    public function testGetRequestPath(): void
    {
        $this->assertEquals('/test/123', $this->request->getRequestPath());
    }

    public function testGet(): void
    {
        $this->assertEquals(1, $this->request->get('getp'));
        $this->assertEquals(1, $this->request->request('getp'));
        $this->assertEquals(['getp' => 1], $this->request->get());
    }

    public function testPost(): void
    {
        $this->assertEquals(2, $this->request->post('postp'));
        $this->assertEquals(2, $this->request->request('postp'));
        $this->assertEquals(['postp' => 2], $this->request->post());
    }

    public function testAppendVars(): void
    {
        $this->request->appendVars(['newp' => 7]);
        $this->assertEquals(7, $this->request->param('newp'));
    }

    public function testEmptyRequest(): void
    {
        $request = new HttpRequest([], [], [], [], [], []);
        $this->assertEmpty($request->getRequestPath());
        $this->assertEmpty($request->server('REQUEST_METHOD'));
    }
}
