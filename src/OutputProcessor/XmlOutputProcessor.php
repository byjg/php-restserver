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

    public function getErrorHandler()
    {
        return new XmlResponseHandler();
    }

    public function getFormatter()
    {
        return new XmlFormatter();
    }
}
