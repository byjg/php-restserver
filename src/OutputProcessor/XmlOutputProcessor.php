<?php

namespace ByJG\RestServer\OutputProcessor;

use ByJG\Serializer\Formatter\XmlFormatter;
use Whoops\Handler\XmlResponseHandler;

class XmlOutputProcessor extends BaseOutputProcessor
{
    public function __construct()
    {
        $this->contentType = "text/xml";
    }

    public function getDetailedErrorHandler()
    {
        return new XmlResponseHandler();
    }

    public function getErrorHandler()
    {
        return $this->getDetailedErrorHandler();
    }

    public function getFormatter()
    {
        return new XmlFormatter();
    }
}
