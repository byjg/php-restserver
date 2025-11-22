<?php

namespace ByJG\RestServer\OutputProcessor;

use ByJG\RestServer\Whoops\TwirpResponseErrorHandler;
use Override;
use Whoops\Handler\Handler;

class JsonTwirpOutputProcessor extends JsonOutputProcessor
{
    #[Override]
    public function getErrorHandler(): Handler
    {
        return new TwirpResponseErrorHandler();
    }
}
