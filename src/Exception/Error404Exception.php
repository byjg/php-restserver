<?php

namespace ByJG\RestServer\Exception;

class Error404Exception extends ClientShowException
{
    public function handleHeader()
    {
        $this->sendHeader(404, 'Not Found');
    }
}
