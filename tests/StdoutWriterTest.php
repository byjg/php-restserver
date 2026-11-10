<?php

namespace Tests;

use ByJG\RestServer\Writer\MemoryWriter;
use PHPUnit\Framework\TestCase;

/**
 * The writer keeps the header lines as PHP's header() would send them (MemoryWriter keeps StdoutWriter's
 * lines instead of printing them)
 */
class StdoutWriterTest extends TestCase
{
    public function testAHeaderReplacesOnlyTheHeaderWithItsName(): void
    {
        $writer = new MemoryWriter();
        $writer->header('X-Foo-Bar: 1');
        $writer->header('X-Foo: 2');
        $writer->header('x-foo: 3');

        $this->assertEquals(['X-Foo-Bar: 1', 'x-foo: 3'], $writer->getHeaders());
    }

    public function testReplacingRemovesEveryLineOfThatName(): void
    {
        $writer = new MemoryWriter();
        $writer->header('Set-Cookie: a=1', false);
        $writer->header('Set-Cookie: b=2', false);
        $writer->header('Content-Type: text/plain');
        $writer->header('Set-Cookie: c=3');

        $this->assertEquals(['Content-Type: text/plain', 'Set-Cookie: c=3'], $writer->getHeaders());
    }

    public function testWithoutReplaceEveryValueIsKept(): void
    {
        $writer = new MemoryWriter();
        $writer->responseCode(200, 'OK');
        $writer->header('X-Values: a', false);
        $writer->header('X-Values: b', false);

        $this->assertEquals(['HTTP/1.1 200 OK', 'X-Values: a', 'X-Values: b'], $writer->getHeaders());
    }
}
