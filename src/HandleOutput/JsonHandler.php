<?php

namespace ByJG\RestServer\HandleOutput;

use ByJG\RestServer\Whoops\JsonResponseHandler;
use ByJG\Serializer\Formatter\JsonFormatter;

class JsonHandler extends BaseHandler
{
    public function __construct()
    {
        $this->header = [
            'Content-Type: application/json'
        ];
    }

    public function getErrorHandler()
    {
        return new JsonResponseHandler();
    }

    public function getFormatter()
    {
        return new JsonFormatter();
    }
}
