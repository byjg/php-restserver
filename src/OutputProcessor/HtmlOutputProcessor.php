<?php

namespace ByJG\RestServer\OutputProcessor;

use ByJG\Serializer\Formatter\PlainTextFormatter;
use Whoops\Handler\PrettyPageHandler;

class HtmlOutputProcessor extends BaseOutputProcessor
{
    public function __construct()
    {
        $this->header = [
            'Content-Type: text/html'
        ];
    }

    public function getErrorHandler()
    {
        return new PrettyPageHandler();
    }

    public function getFormatter()
    {
        return new PlainTextFormatter();
    }
}
