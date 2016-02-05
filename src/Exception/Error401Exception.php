<?php

namespace ByJG\RestServer\Exception;

class Error401Exception extends ClientShowException
{
    public function handleHeader()
    {
        $this->sendHeader(401, 'Unathorized');
    }
}
