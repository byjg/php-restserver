<?php

namespace ByJG\RestServer\Exception;

class Error409Exception extends ClientShowException
{
    public function handleHeader()
    {
        $this->sendHeader(409, 'Conflict');
    }
}
