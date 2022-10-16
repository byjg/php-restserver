<?php

namespace Tests;

use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\OutputProcessor\HtmlOutputProcessor;
use ByJG\RestServer\OutputProcessor\JsonCleanOutputProcessor;
use ByJG\RestServer\OutputProcessor\JsonOutputProcessor;
use ByJG\RestServer\OutputProcessor\XmlOutputProcessor;
use PHPUnit\Framework\TestCase;

class OutputProcessorTest extends TestCase
{
    protected $object;

    /**
     * @var HttpResponse
     */
    protected $httpResponse;

    protected $result;

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

    public function tearDown(): void
    {
        $this->object = null;
        $this->result = null;
        $this->httpResponse = null;
    }

    public function dataProvider()
    {
        return [
            [
                JsonOutputProcessor::class,
                "application/json",
                '{"name":"teste","address1":"","address2":null,"value":0}',
                '{"name":"teste","address1":"","address2":null,"value":0}',
            ],
            [
                JsonCleanOutputProcessor::class,
                "application/json",
                '{"name":"teste","address1":"","address2":null,"value":0}',
                '{"name":"teste","address1":"","value":0}',
            ],
            [
                XmlOutputProcessor::class,
                "text/xml",
                "<?xml version=\"1.0\"?>\n<root><name>teste</name><address1/><address2/><value>0</value></root>\n",
                "<?xml version=\"1.0\"?>\n<root><name>teste</name><address1/><address2/><value>0</value></root>\n",
            ],
            [
                HtmlOutputProcessor::class,
                "text/html",
                "teste\n\n\n0\n",
                "teste\n\n\n0\n",
            ],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testOutputProcessor($class, $contentType, $expectedProcess, $expectedResponse)
    {
        // Mock object
        $processor =  $this->getMockBuilder($class)
            ->setMethods(["writeData"])
            ->getMock();
        $processor->expects($this->once())
            ->method('writeData')
            ->willReturnCallback(function ($data) { $this->result = $data;});

        // Run Basic Tests
        $this->assertEquals($contentType, $processor->getContentType());
        $this->assertEquals(
            $expectedProcess,
            $processor->getFormatter()->process($this->object)
        );

        // Run Process Response
        $processor->processResponse($this->httpResponse);

        $this->assertEquals($expectedResponse, $this->result);
    }

}
