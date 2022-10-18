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
            "application/json" => JsonOutputProcessor::class,
            "*/*" => JsonOutputProcessor::class,
        ];

        if (!isset($mimeTypeOutputProcessor[$contentType])) {
            throw new OperationIdInvalidException("There is no output rocessor for $contentType");
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
        if (defined("RESTSERVER_TEST")) {
            return;
        }
        header("Content-Type: " . $this->contentType);
    }

    public function getContentType()
    {
        return $this->contentType;
    }

    protected function writeHeader(HttpResponse $response)
    {
        foreach ($response->getHeaders() as $header => $value) {
            if (is_array($value)) {
                header("$header: " . array_shift($value), true);
                foreach ($value as $headerValue) {
                    header("$header: $headerValue", false);
                }
            } else {
                header("$header: $value", true);
            }
        }

        http_response_code($response->getResponseCode());
    }

    public function writeData($data)
    {
        echo $data;
    }

    public function processResponse(HttpResponse $response)
    {
        $this->writeHeader($response);

        $serialized = $response
            ->getResponseBag()
            ->process($this->buildNull, $this->onlyString);

        $this->writeData(
            $this->getFormatter()->process($serialized)
        );
    }
}
