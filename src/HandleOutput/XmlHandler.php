<?php

namespace ByJG\RestServer\HandleOutput;

use ByJG\Serializer\Formatter\XmlFormatter;
use Whoops\Handler\XmlResponseHandler;

class XmlHandler extends BaseHandler
{
    public function __construct()
    {
        $this->header = [
            'Content-Type: text/xml'
        ];
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
