<?php

namespace ByJG\RestServer\OutputProcessor;

use ByJG\RestServer\Exception\OperationIdInvalidException;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\ResponseBag;
use ByJG\RestServer\Writer\WriterInterface;

abstract class BaseOutputProcessor implements OutputProcessorInterface
{
    protected $buildNull = true;
    protected $onlyString = false;
    protected $header = [];
    protected $contentType = "";

    /** @var WriterInterface */
    protected $writer;

    public function setWriter(WriterInterface $writer)
    {
        $this->writer = $writer;
    }

    public static function getFromContentType($contentType)
    {
        $mimeTypeOutputProcessor = [
            "text/xml" => XmlOutputProcessor::class,
            "application/xml" => XmlOutputProcessor::class,
            "text/html" => HtmlOutputProcessor::class,
            "application/json" => JsonOutputProcessor::class,
            "*/*" => JsonOutputProcessor::class,
        ];

        if (!isset($mimeTypeOutputProcessor[$contentType])) {
            throw new OperationIdInvalidException("There is no output processor for $contentType");
        }

        return $mimeTypeOutputProcessor[$contentType];
    }
    
    public static function getFromHttpAccept()
    {
        $accept = isset($_SERVER["HTTP_ACCEPT"]) ? $_SERVER["HTTP_ACCEPT"] : "application/json";
        
        $acceptList = explode(",", $accept);
        
        return self::getFromClassName(self::getFromContentType($acceptList[0]));
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
        $this->writer->header("Content-Type: $this->contentType", true);
    }

    public function getContentType()
    {
        return $this->contentType;
    }

    public function writeHeader(HttpResponse $response)
    {
        $this->writer->responseCode($response->getResponseCode(), $response->getResponseCodeDescription());

        foreach ($response->getHeaders() as $header => $value) {
            if (is_array($value)) {
                $this->writer->header("$header: " . array_shift($value), true);
                foreach ($value as $headerValue) {
                    $this->writer->header("$header: $headerValue", false);
                }
            } else {
                $this->writer->header("$header: $value", true);
            }
        }
    }

    public function writeData($data)
    {
        $this->writer->echo($data);
    }

    public function processResponse(HttpResponse $response)
    {
        $this->writeHeader($response);

        $serialized = $response
            ->getResponseBag()
            ->process($this->buildNull, $this->onlyString);

        if ($response->getResponseBag()->getSerializationRule() === ResponseBag::RAW) {
            $this->writeData($serialized);
        } else {
            $this->writeData(
                $this->getFormatter()->process($serialized)
            );
        }

        $this->writer->flush();
    }
}
