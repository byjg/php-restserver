<?php

namespace ByJG\RestServer\Whoops;

use ByJG\RestServer\ErrorHandler;
use Override;
use Whoops\Handler\Handler;

class LoggerErrorHandler extends Handler
{

    /**
     * @inheritDoc
     */
    #[Override]
    public function handle()
    {
        ErrorHandler::getInstance()->getLogger()->error($this->getException()->getMessage(), ['exception' => $this->getException()]);

        return Handler::DONE;
    }
}