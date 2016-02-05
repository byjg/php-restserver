<?php

namespace ByJG\RestServer\Exception;

class Error520Exception extends ClientShowException
{
    public function handleHeader()
    {
        $this->sendHeader(520, 'Unknow Error');
    }
}
