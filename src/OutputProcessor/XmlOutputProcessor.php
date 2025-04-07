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

    /**
     * @return XmlResponseHandler
     */
    #[\Override]
    public function getDetailedErrorHandler(): Handler
    {
        return new XmlResponseHandler();
    }

    #[\Override]
    public function getErrorHandler(): Handler
    {
        return $this->getDetailedErrorHandler();
    }

    /**
     * @return XmlFormatter
     */
    #[\Override]
    public function getFormatter(): FormatterInterface
    {
        return new XmlFormatter();
    }
}
