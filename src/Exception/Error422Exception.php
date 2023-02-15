<?php

namespace ByJG\RestServer\Exception;

class Error422Exception extends ClientShowException
{
    public function handleHeader()
    {
        $this->sendHeader(422, 'Unprocessable Entity');
    }
}
