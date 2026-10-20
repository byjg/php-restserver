<?php

namespace Tests;

use ByJG\RestServer\Exception\Error403Exception;
use ByJG\RestServer\Middleware\BlockPathMiddleware;
use PHPUnit\Framework\TestCase;

class ServerBlockPathMiddlewareTest extends TestCase
{
    use MockServerTrait;

    public function testAllowedPath(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test';
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        $this->processAndGetContent(
            $this->object,
            null,
            '{"key":"value"}',
            (new BlockPathMiddleware())->withBlockedStartWith(['/admin'])
        );

        $this->assertTrue($this->reach);
    }

    public function testBlockedByStartWith(): void
    {
        $this->expectException(Error403Exception::class);
        $this->expectExceptionMessage("Access denied");

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/admin/users';
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        $this->processAndGetContent(
            $this->object,
            null,
            '{"error":{"type":"Error 403","message":"Access denied"}}',
            (new BlockPathMiddleware())->withBlockedStartWith(['/admin'])
        );
    }

    public function testBlockedByStartWithMultiplePrefixes(): void
    {
        $this->expectException(Error403Exception::class);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/internal/config';
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        $this->processAndGetContent(
            $this->object,
            null,
            '{"error":{"type":"Error 403","message":"Access denied"}}',
            (new BlockPathMiddleware())->withBlockedStartWith(['/admin', '/internal'])
        );
    }

    public function testBlockedByRegEx(): void
    {
        $this->expectException(Error403Exception::class);
        $this->expectExceptionMessage("Access denied");

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/files/secret.env';
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        $this->processAndGetContent(
            $this->object,
            null,
            '{"error":{"type":"Error 403","message":"Access denied"}}',
            (new BlockPathMiddleware())->withBlockedRegEx(['~\.env$~'])
        );
    }

    public function testAllowedByRegEx(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test';
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        $this->processAndGetContent(
            $this->object,
            null,
            '{"key":"value"}',
            (new BlockPathMiddleware())->withBlockedRegEx(['~\.env$~'])
        );

        $this->assertTrue($this->reach);
    }

    public function testBlockedByCombined(): void
    {
        $this->expectException(Error403Exception::class);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/files/secret.env';
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        $this->processAndGetContent(
            $this->object,
            null,
            '{"error":{"type":"Error 403","message":"Access denied"}}',
            (new BlockPathMiddleware())
                ->withBlockedStartWith(['/admin'])
                ->withBlockedRegEx(['~\.env$~'])
        );
    }

    public function testNoRulesAllowsAll(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test';
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        $this->processAndGetContent(
            $this->object,
            null,
            '{"key":"value"}',
            new BlockPathMiddleware()
        );

        $this->assertTrue($this->reach);
    }
}