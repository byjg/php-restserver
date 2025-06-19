<?php

namespace ByJG\RestServer\OutputProcessor;

use ByJG\RestServer\Whoops\PlainResponseErrorHandler;
use ByJG\Serializer\Formatter\FormatterInterface;
use ByJG\Serializer\Formatter\PlainTextFormatter;
use Override;
use Whoops\Handler\Handler;

class PlainTextOutputProcessor extends BaseOutputProcessor
{

    public function __construct()
    {
        $this->contentType = "text/plain";
    }

    #[Override]
    public function getDetailedErrorHandler(): Handler
    {
        return new PlainResponseErrorHandler();
    }

    #[Override]
    public function getErrorHandler(): Handler
    {
        return $this->getDetailedErrorHandler();
    }

    #[Override]
    public function getFormatter(): FormatterInterface
    {
        return new PlainTextFormatter();
    }
}