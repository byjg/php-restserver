<?php

namespace ByJG\RestServer\Exception;

class Error403Exception extends ClientShowException
{
    public function handleHeader()
    {
        $this->sendHeader(403, 'Forbidden');
    }
}
