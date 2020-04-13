<?php

namespace ByJG\RestServer\OutputProcessor;

use ByJG\RestServer\Exception\OperationIdInvalidException;
use ByJG\RestServer\HttpResponse;

abstract class BaseOutputProcessor implements OutputProcessorInterface
{
    protected $buildNull = true;
    protected $onlyString = false;
    protected $header = [];

    public static function getOutputProcessorClass($contentType)
    {
        $mimeTypeOutputProcessor = [
            "text/xml" => XmlOutputProcessor::class,
            "application/xml" => XmlOutputProcessor::class,
            "text/html" => HtmlOutputProcessor::class,
            "application/json" => JsonOutputProcessor::class
        ];

        if (!isset($mimeTypeOutputProcessor[$contentType])) {
            throw new OperationIdInvalidException("There is no output rocessor for $contentType");
        }

        return $mimeTypeOutputProcessor[$contentType];
    }

    public static function getOutputProcessorInstance($contentType)
    {
        $class = self::getOutputProcessorClass($contentType);

        return new $class();
    }

    public function writeHeader($headerList = null)
    {
        if ($headerList === null) {
            $headerList = $this->header;
        }
        foreach ($headerList as $header) {
            if (is_array($header)) {
                header($header[0], $header[1]);
                continue;
            }
            header($header);
        }
    }

    public function writeData($data)
    {
        echo $data;
    }

    public function processResponse(HttpResponse $response)
    {
        $instanceHeaders = $response->getHeaders();
        $this->writeHeader($instanceHeaders);

        http_response_code($response->getResponseCode());

        $serialized = $response
            ->getResponseBag()
            ->process($this->buildNull, $this->onlyString);

        $this->writeData(
            $this->getFormatter()->process($serialized)
        );
    }
}
