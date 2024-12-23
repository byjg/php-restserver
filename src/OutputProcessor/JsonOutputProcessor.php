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

    /**
     * @return JsonResponseHandler
     */
    public function getDetailedErrorHandler(): Handler
    {
        $jsonHandler = new JsonResponseHandler();
        $jsonHandler->addTraceToOutput(true);
        return $jsonHandler;
    }

    /**
     * @return JsonLimitedResponseHandler
     */
    public function getErrorHandler(): Handler
    {
        return new JsonLimitedResponseHandler();
    }

    /**
     * @return JsonFormatter
     */
    public function getFormatter(): FormatterInterface
    {
        return new JsonFormatter();
    }
}
