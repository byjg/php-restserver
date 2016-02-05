<?php

namespace ByJG\RestServer\Exception;

class Error405Exception extends ClientShowException
{
    public function handleHeader()
    {
        $this->sendHeader(405, 'Method not allowed');
    }
}
