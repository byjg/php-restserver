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

    protected function writeHeader($headerList)
    {
        foreach ($headerList as $header) {
            if (is_array($header)) {
                echo "${header[0]}: ${header[1]}\n";
                continue;
            }
            echo "$header\n";
        }
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
        // Do nothing;
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

    /**
     * @param HttpResponse $response
     * @return string
     */
    public function processResponse(HttpResponse $response)
    {
        echo "HTTP/1.1 " . $response->getResponseCode() . "\r\n";
        echo "Content-Type: " . $this->getContentType() . "\r\n";
        $instanceHeaders = $response->getHeaders();
        $this->writeHeader($instanceHeaders);

        echo "\r\n";

        $serialized = $response
            ->getResponseBag()
            ->process($this->buildNull, $this->onlyString);

        $this->writeData(
            $this->getFormatter()->process($serialized)
        );
    }

}
