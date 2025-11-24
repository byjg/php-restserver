<?php

namespace Tests\OutputProcessor;

use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\OutputProcessor\PlainTextOutputProcessor;
use ByJG\RestServer\Writer\MemoryWriter;
use Override;
use PHPUnit\Framework\TestCase;

class PlainTextOutputProcessorTest extends TestCase
{
    protected ?array $object;
    protected ?HttpResponse $httpResponse;

    #[Override]
    public function setup(): void
    {
        $this->object = [
            "name" => "teste",
            "address1" => "",
            "address2" => null,
            "value" => 0
        ];

        $this->httpResponse = new HttpResponse();
        $this->httpResponse->write($this->object);
    }

    #[Override]
    public function tearDown(): void
    {
        $this->object = null;
        $this->httpResponse = null;
    }

    public function testContentType(): void
    {
        $processor = new PlainTextOutputProcessor();
        $this->assertEquals("text/plain", $processor->getContentType());
    }

    public function testFormatter(): void
    {
        $processor = new PlainTextOutputProcessor();
        $expected = "teste\n\n\n0\n";
        $this->assertEquals($expected, $processor->getFormatter()->process($this->object));
    }

    public function testProcessResponse(): void
    {
        $writer = new MemoryWriter();
        $processor = new PlainTextOutputProcessor();
        $processor->setWriter($writer);

        $processor->processResponse($this->httpResponse);

        $expected = "teste\n\n\n0\n";
        $this->assertEquals($expected, $writer->getData());
    }

    public function testFormatterWithObject(): void
    {
        $model = new TestModel("teste", "", null, 0);
        $processor = new PlainTextOutputProcessor();
        $expected = "teste\n\n\n0\n";
        $this->assertEquals($expected, $processor->getFormatter()->process($model));
    }

    public function testProcessResponseWithObject(): void
    {
        $model = new TestModel("teste", "", null, 0);
        $response = new HttpResponse();
        $response->write($model);

        $writer = new MemoryWriter();
        $processor = new PlainTextOutputProcessor();
        $processor->setWriter($writer);

        $processor->processResponse($response);

        $expected = "teste\n\n\n0\n";
        $this->assertEquals($expected, $writer->getData());
    }
}