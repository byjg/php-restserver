<?php

namespace ByJG\RestServer\Middleware;

class MiddlewareResult
{
    const STOP_PROCESSING_OTHERS = 'STOP_PROCESSING_OTHERS';
    const CONTINUE = 'CONTINUE';
    const STOP_PROCESSING = 'STOP_PROCESSING';

    protected $status = null;

    protected function __construct($status)
    {
        $this->status = $status;
    }

    public static function stopProcessingOthers()
    {
        return new MiddlewareResult(MiddlewareResult::STOP_PROCESSING_OTHERS);
    }

    public static function stopProcessing()
    {
        return new MiddlewareResult(MiddlewareResult::STOP_PROCESSING);
    }

    public static function continue()
    {
        return new MiddlewareResult(MiddlewareResult::CONTINUE);
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getOutputProcessorClass()
    {
        return $this->outputProcessorClass;
    }
}