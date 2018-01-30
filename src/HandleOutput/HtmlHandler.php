<?php

namespace ByJG\RestServer\HandleOutput;

use ByJG\Serializer\Formatter\PlainTextFormatter;
use Whoops\Handler\PrettyPageHandler;

class HtmlHandler extends BaseHandler
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
