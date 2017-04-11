<?php

namespace ByJG\RestServer\Whoops;

use ByJG\RestServer\Exception\ClientShowException;

trait WhoopsHeaderTrait
{
    public function setProperHeader($exception)
    {
        if ($exception instanceof ClientShowException) {
            $exception->handleHeader();
        }
    }
}
