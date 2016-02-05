<?php

namespace ByJG\RestServer\Exception;

class Error501Exception extends ClientShowException
{
    public function handleHeader()
    {
        $this->sendHeader(501, 'Not Implemented');
    }
}
