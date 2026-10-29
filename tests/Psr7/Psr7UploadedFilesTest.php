<?php

namespace Tests\Psr7;

use ByJG\RestServer\Psr7\Psr7RequestAdapter;
use ByJG\RestServer\Psr7\Psr7UploadedFiles;
use ByJG\Util\Uri;
use ByJG\WebRequest\Psr7\ServerRequest;
use ByJG\WebRequest\Psr7\UploadedFile;
use InvalidArgumentException;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class Psr7UploadedFilesTest extends TestCase
{
    private string $target;

    #[Override]
    protected function setUp(): void
    {
        $_FILES = [];
        $this->target = sys_get_temp_dir() . '/psr7-uploads-' . uniqid();
        mkdir($this->target);
    }

    #[Override]
    protected function tearDown(): void
    {
        $_FILES = [];
        array_map('unlink', glob($this->target . '/*') ?: []);
        rmdir($this->target);
    }

    private function request(array $files): ServerRequestInterface
    {
        return (new ServerRequest(Uri::getInstance('http://localhost/upload'), ['REMOTE_ADDR' => '127.0.0.1']))
            ->withMethod('POST')
            ->withUploadedFiles($files);
    }

    public function testReadsTheUploadedFilesOfTheRequest(): void
    {
        $upload = new Psr7UploadedFiles($this->request([
            'test' => new UploadedFile('test', 4, UPLOAD_ERR_OK, 'test.txt', 'text/plain'),
            'failed' => new UploadedFile('', 0, UPLOAD_ERR_PARTIAL, 'big.bin', 'application/octet-stream'),
        ]));

        $this->assertEquals(2, $upload->count());
        $this->assertEquals(['test', 'failed'], $upload->getKeys());
        $this->assertTrue($upload->isOk('test'));
        $this->assertSame(UPLOAD_ERR_OK, $upload->getErrorCode('test'));
        $this->assertFalse($upload->isOk('failed'));
        $this->assertSame(UPLOAD_ERR_PARTIAL, $upload->getErrorCode('failed'));
        $this->assertEquals('test.txt', $upload->getFileName('test'));
        $this->assertEquals('text/plain', $upload->getFileType('test'));
        $this->assertEquals(4, $upload->getFileSize('test'));
        $this->assertEquals('test', $upload->getUploadedFile('test'));
    }

    public function testSaveTo(): void
    {
        $upload = new Psr7UploadedFiles($this->request([
            'test' => new UploadedFile('content', 7, UPLOAD_ERR_OK, 'test.txt', 'text/plain'),
        ]));

        $upload->saveTo('test', $this->target);
        $this->assertEquals('content', file_get_contents($this->target . '/test.txt'));
    }

    /**
     * @return string[][]
     */
    public static function invalidFileNameProvider(): array
    {
        return [
            'client name with parent directory' => ['../../app.php', ''],
            'client name with absolute path' => ['/etc/cron.d/job', ''],
            'new name with parent directory' => ['test.txt', '../app.php'],
            'new name is parent directory' => ['test.txt', '..'],
        ];
    }

    #[DataProvider('invalidFileNameProvider')]
    public function testSaveToRejectsPath(string $clientName, string $newName): void
    {
        $upload = new Psr7UploadedFiles($this->request([
            'test' => new UploadedFile('test', 4, UPLOAD_ERR_OK, $clientName, 'text/plain'),
        ]));

        $this->expectException(InvalidArgumentException::class);
        $upload->saveTo('test', $this->target, $newName);
    }

    public function testUnknownKey(): void
    {
        $upload = new Psr7UploadedFiles($this->request([]));

        $this->expectException(InvalidArgumentException::class);
        $upload->getFileName('missing');
    }

    /**
     * Nothing is read until an upload is used: a request whose files are never touched costs nothing
     */
    public function testReadsTheFilesOnlyWhenUsed(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->never())->method('getUploadedFiles');

        new Psr7UploadedFiles($request);
    }

    /**
     * The adapter gives the HttpRequest the uploaded files of the PSR-7 request, not $_FILES
     */
    public function testToHttpRequestUsesTheRequestFiles(): void
    {
        $_FILES = ['other' => ['name' => 'x', 'type' => '', 'tmp_name' => '', 'error' => UPLOAD_ERR_NO_FILE, 'size' => 0]];

        $httpRequest = Psr7RequestAdapter::toHttpRequest($this->request([
            'avatar' => new UploadedFile('PNGDATA', 7, UPLOAD_ERR_OK, 'me.png', 'image/png'),
        ]));

        $this->assertEquals(['avatar'], $httpRequest->uploadedFiles()->getKeys());
        $this->assertEquals('PNGDATA', $httpRequest->uploadedFiles()->getUploadedFile('avatar'));
    }
}
