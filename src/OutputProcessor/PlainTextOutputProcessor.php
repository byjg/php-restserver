<?php

namespace ByJG\RestServer\OutputProcessor;

use ByJG\RestServer\Whoops\PlainResponseErrorHandler;
use ByJG\Serializer\Formatter\FormatterInterface;
use ByJG\Serializer\Formatter\PlainTextFormatter;
use Whoops\Handler\Handler;

class PlainTextOutputProcessor extends BaseOutputProcessor
{

    public function __construct()
    {
        $this->contentType = "text/plain";
    }

    public function getDetailedErrorHandler(): Handler
    {
        return new PlainResponseErrorHandler();
    }

    public function getErrorHandler(): Handler
    {
        $this->getDetailedErrorHandler();
    }

    public function getFormatter(): FormatterInterface
    {
        return new PlainTextFormatter();
    }
}