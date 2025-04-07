<?php

namespace ByJG\RestServer\OutputProcessor;

use ByJG\RestServer\Whoops\TwirpResponseErrorHandler;
use Whoops\Handler\Handler;

class JsonTwirpOutputProcessor extends JsonOutputProcessor
{
    #[\Override]
    public function getErrorHandler(): Handler
    {
        return new TwirpResponseErrorHandler();
    }
}
