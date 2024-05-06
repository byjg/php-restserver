<?php

namespace ByJG\RestServer\OutputProcessor;

use ByJG\Serializer\Formatter\FormatterInterface;
use ByJG\Serializer\Formatter\XmlFormatter;
use Whoops\Handler\Handler;
use Whoops\Handler\XmlResponseHandler;

class XmlOutputProcessor extends BaseOutputProcessor
{
    public function __construct()
    {
        $this->contentType = "text/xml";
    }

    public function getDetailedErrorHandler(): Handler
    {
        return new XmlResponseHandler();
    }

    public function getErrorHandler(): Handler
    {
        return $this->getDetailedErrorHandler();
    }

    public function getFormatter(): FormatterInterface
    {
        return new XmlFormatter();
    }
}
