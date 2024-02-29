<?php

namespace ByJG\RestServer\OutputProcessor;

use ByJG\RestServer\Whoops\JsonLimitedResponseHandler;
use ByJG\Serializer\Formatter\JsonFormatter;
use Whoops\Handler\JsonResponseHandler;

class JsonOutputProcessor extends BaseOutputProcessor
{
    public function __construct()
    {
        $this->contentType = "application/json";
    }

    public function getDetailedErrorHandler()
    {
        $jsonHandler = new JsonResponseHandler();
        $jsonHandler->addTraceToOutput(true);
        return $jsonHandler;
    }

    public function getErrorHandler()
    {
        return new JsonLimitedResponseHandler();
    }

    public function getFormatter()
    {
        return new JsonFormatter();
    }
}
