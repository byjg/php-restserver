<?php

namespace Tests\OutputProcessor;

use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\OutputProcessor\CsvOutputProcessor;
use ByJG\RestServer\Writer\MemoryWriter;
use Override;
use PHPUnit\Framework\TestCase;
use Whoops\Handler\PlainTextHandler;

class CsvOutputProcessorTest extends TestCase
{
    protected $object;
    protected $httpResponse;

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
        $processor = new CsvOutputProcessor();
        $this->assertEquals("text/csv", $processor->getContentType());
    }

    public function testFormatter(): void
    {
        $processor = new CsvOutputProcessor();
        $expected = "name,address1,address2,value\nteste,,,0\n";
        $this->assertEquals($expected, $processor->getFormatter()->process($this->object));
    }

    public function testProcessResponse(): void
    {
        $writer = new MemoryWriter();
        $processor = new CsvOutputProcessor();
        $processor->setWriter($writer);

        $processor->processResponse($this->httpResponse);

        $expected = "name,address1,address2,value\nteste,,,0\n";
        $this->assertEquals($expected, $writer->getData());
    }

    public function testErrorHandler(): void
    {
        $processor = new CsvOutputProcessor();
        $errorHandler = $processor->getErrorHandler();

        $this->assertInstanceOf(PlainTextHandler::class, $errorHandler);
    }

    public function testDetailedErrorHandler(): void
    {
        $processor = new CsvOutputProcessor();
        $detailedErrorHandler = $processor->getDetailedErrorHandler();

        $this->assertInstanceOf(PlainTextHandler::class, $detailedErrorHandler);
    }
}
