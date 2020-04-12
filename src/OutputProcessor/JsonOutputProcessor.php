<?php

namespace ByJG\RestServer\OutputProcessor;

use ByJG\RestServer\Whoops\JsonResponseErrorHandler;
use ByJG\Serializer\Formatter\JsonFormatter;

class JsonOutputProcessor extends BaseOutputProcessor
{
    public function __construct()
    {
        $this->header = [
            'Content-Type: application/json'
        ];
    }

    public function getErrorHandler()
    {
        return new JsonResponseErrorHandler();
    }

    public function getFormatter()
    {
        return new JsonFormatter();
    }
}
