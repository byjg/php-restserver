<?php

namespace ByJG\RestServer\Exception;

class Error415Exception extends ClientShowException
{
    public function handleHeader()
    {
        $this->sendHeader(415, 'Unsupported Media Type');
    }
}
