<?php

namespace ByJG\RestServer\OutputProcessor;

use ByJG\RestServer\Exception\OperationIdInvalidException;
use ByJG\RestServer\HttpResponse;

abstract class BaseOutputProcessor implements OutputProcessorInterface
{
    protected $buildNull = true;
    protected $onlyString = false;
    protected $header = [];
    protected $contentType = "";

    public static function getFromContentType($contentType)
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

    /**
     * @param $className
     * @return OutputProcessorInterface
     */
    public static function getFromClassName($className)
    {
        if ($className instanceof \Closure) {
            return $className();
        }
        return new $className();
    }

    public static function getOutputProcessorInstance($contentType)
    {
        $class = self::getFromContentType($contentType);

        return new $class();
    }

    public function writeContentType()
    {
        header("Content-Type: " . $this->contentType);
    }

    public function getContentType()
    {
        return $this->contentType;
    }

    protected function writeHeader($headerList)
    {
        foreach ($headerList as $header) {
            if (is_array($header)) {
                $this->header($header[0], $header[1]);
                continue;
            }
            $this->header($header);
        }
    }

    protected function writeData($data)
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
