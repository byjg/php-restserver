<?php

namespace ByJG\RestServer\OutputProcessor;

use ByJG\RestServer\Whoops\PlainResponseErrorHandler;
use ByJG\Serializer\Formatter\PlainTextFormatter;
use Whoops\Handler\PrettyPageHandler;

class HtmlOutputProcessor extends BaseOutputProcessor
{
    public function __construct()
    {
        $this->contentType = "text/html";
    }

    public function getDetailedErrorHandler()
    {
        return new PrettyPageHandler();
    }

    public function getErrorHandler()
    {
        return new PlainResponseErrorHandler();
    }

    public function getFormatter()
    {
        return new PlainTextFormatter();
    }
}
