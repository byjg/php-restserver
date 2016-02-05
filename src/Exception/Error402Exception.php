<?php

namespace ByJG\RestServer\Exception;

class Error402Exception extends ClientShowException
{
    public function handleHeader()
    {
        $this->sendHeader(402, 'Payment Required');
    }
}
