<?php

namespace ByJG\RestServer\Exception;

class Error406Exception extends ClientShowException
{
    public function handleHeader()
    {
        $this->sendHeader(405, 'Not Acceptable');
    }
}
