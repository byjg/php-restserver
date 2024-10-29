<?php

namespace ByJG\RestServer\OutputProcessor;

use ByJG\RestServer\Whoops\PlainResponseErrorHandler;
use ByJG\Serializer\Formatter\FormatterInterface;
use ByJG\Serializer\Formatter\PlainTextFormatter;
use Whoops\Handler\Handler;
use Whoops\Handler\PrettyPageHandler;

class HtmlOutputProcessor extends BaseOutputProcessor
{
    public function __construct()
    {
        $this->contentType = "text/html";
    }

    /**
     * @return PrettyPageHandler
     */
    public function getDetailedErrorHandler(): Handler
    {
        return new PrettyPageHandler();
    }

    /**
     * @return PlainResponseErrorHandler
     */
    public function getErrorHandler(): Handler
    {
        return new PlainResponseErrorHandler();
    }

    /**
     * @return PlainTextFormatter
     */
    public function getFormatter(): FormatterInterface
    {
        return new PlainTextFormatter();
    }
}
