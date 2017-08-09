<?php

namespace ByJG\RestServer\Exception;

class Error429Exception extends ClientShowException
{
    public function handleHeader()
    {
        $this->sendHeader(429, 'Too many requests');
    }
}
