<?php

namespace ByJG\RestServer\OutputProcessor;

use ByJG\RestServer\HttpResponse;
use ByJG\Serializer\Formatter\FormatterInterface;
use Whoops\Handler\Handler;

class MockOutputProcessor extends BaseOutputProcessor
{
    /**
     * @var OutputProcessorInterface
     */
    protected $originalOutputProcessor;

    public function __construct($class)
    {
        $this->originalOutputProcessor = new $class();
    }

    public function writeHeader(HttpResponse $response)
    {
        $headerList = $response->getHeaders();
        $contentType = $this->getContentType();
        if (isset($headerList["Content-Type"])) {
            $contentType = $headerList["Content-Type"];
            unset($headerList["Content-Type"]);
        }
        echo "HTTP/1.1 " . $response->getResponseCode() . "\r\n";
        echo "Content-Type: " . $contentType . "\r\n";

        foreach ($headerList as $header => $value) {
            if (is_array($value)) {
                foreach ($value as $headerValue) {
                    echo "$header: $headerValue\r\n";
                }
            } else {
                echo "$header: $value\r\n";
            }
        }
        echo "\r\n";
    }

    public function getContentType()
    {
        return $this->originalOutputProcessor->getContentType();
    }


    /**
     * @return void
     */
    public function writeContentType()
    {
        return null;
    }


    /**
     * @return Handler
     */
    public function getDetailedErrorHandler()
    {
        return $this->originalOutputProcessor->getDetailedErrorHandler();
    }

    /**
     * @return Handler
     */
    public function getErrorHandler()
    {
        return $this->originalOutputProcessor->getErrorHandler();
    }


    /**
     * @return FormatterInterface
     */
    public function getFormatter()
    {
        return $this->originalOutputProcessor->getFormatter();
    }
}
