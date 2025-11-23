<?php

namespace Tests\OutputProcessor;

use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\OutputProcessor\XmlOutputProcessor;
use ByJG\RestServer\Writer\MemoryWriter;
use Override;
use PHPUnit\Framework\TestCase;

class XmlOutputProcessorTest extends TestCase
{
    protected array $object;
    protected HttpResponse $httpResponse;

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
        $processor = new XmlOutputProcessor();
        $this->assertEquals("application/xml", $processor->getContentType());
    }

    public function testFormatter(): void
    {
        $processor = new XmlOutputProcessor();
        $expected = '<?xml version="1.0"?>' . "\n<root><name>teste</name><address1/><address2/><value>0</value></root>\n";
        $this->assertEquals($expected, $processor->getFormatter()->process($this->object));
    }

    public function testProcessResponse(): void
    {
        $writer = new MemoryWriter();
        $processor = new XmlOutputProcessor();
        $processor->setWriter($writer);

        $processor->processResponse($this->httpResponse);

        $expected = '<?xml version="1.0"?>' . "\n<root><name>teste</name><address1/><address2/><value>0</value></root>\n";
        $this->assertEquals($expected, $writer->getData());
    }

    public function testFormatterWithObject(): void
    {
        $model = new TestModel("teste", "", null, 0);
        $processor = new XmlOutputProcessor();
        $expected = '<?xml version="1.0"?>' . "\n<root><name>teste</name><address1/><address2/><value>0</value></root>\n";
        $this->assertEquals($expected, $processor->getFormatter()->process($model));
    }

    public function testProcessResponseWithObject(): void
    {
        $model = new TestModel("teste", "", null, 0);
        $response = new HttpResponse();
        $response->write($model);

        $writer = new MemoryWriter();
        $processor = new XmlOutputProcessor();
        $processor->setWriter($writer);

        $processor->processResponse($response);

        $expected = '<?xml version="1.0"?>' . "\n<root><name>teste</name><address1/><address2/><value>0</value></root>\n";
        $this->assertEquals($expected, $writer->getData());
    }
}
