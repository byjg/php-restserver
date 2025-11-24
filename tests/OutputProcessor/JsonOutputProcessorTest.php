<?php

namespace Tests\OutputProcessor;

use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\OutputProcessor\JsonOutputProcessor;
use ByJG\RestServer\Writer\MemoryWriter;
use Override;
use PHPUnit\Framework\TestCase;

class JsonOutputProcessorTest extends TestCase
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
        $processor = new JsonOutputProcessor();
        $this->assertEquals("application/json", $processor->getContentType());
    }

    public function testFormatter(): void
    {
        $processor = new JsonOutputProcessor();
        $expected = '{"name":"teste","address1":"","address2":null,"value":0}';
        $this->assertEquals($expected, $processor->getFormatter()->process($this->object));
    }

    public function testProcessResponse(): void
    {
        $writer = new MemoryWriter();
        $processor = new JsonOutputProcessor();
        $processor->setWriter($writer);

        $processor->processResponse($this->httpResponse);

        $expected = '{"name":"teste","address1":"","address2":null,"value":0}';
        $this->assertEquals($expected, $writer->getData());
    }

    public function testFormatterWithObject(): void
    {
        $model = new TestModel("teste", "", null, 0);
        $processor = new JsonOutputProcessor();
        $expected = '{"name":"teste","address1":"","address2":null,"value":0}';
        $this->assertEquals($expected, $processor->getFormatter()->process($model));
    }

    public function testProcessResponseWithObject(): void
    {
        $model = new TestModel("teste", "", null, 0);
        $response = new HttpResponse();
        $response->write($model);

        $writer = new MemoryWriter();
        $processor = new JsonOutputProcessor();
        $processor->setWriter($writer);

        $processor->processResponse($response);

        $expected = '{"name":"teste","address1":"","address2":null,"value":0}';
        $this->assertEquals($expected, $writer->getData());
    }
}