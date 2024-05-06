<?php

namespace ByJG\RestServer\OutputProcessor;

use ByJG\RestServer\Whoops\JsonLimitedResponseHandler;
use ByJG\Serializer\Formatter\FormatterInterface;
use ByJG\Serializer\Formatter\JsonFormatter;
use Whoops\Handler\Handler;
use Whoops\Handler\JsonResponseHandler;

class JsonOutputProcessor extends BaseOutputProcessor
{
    public function __construct()
    {
        $this->contentType = "application/json";
    }

    public function getDetailedErrorHandler(): Handler
    {
        return new JsonResponseHandler();
    }

    public function getErrorHandler(): Handler
    {
        return new JsonLimitedResponseHandler();
    }

    public function getFormatter(): FormatterInterface
    {
        return new JsonFormatter();
    }
}
