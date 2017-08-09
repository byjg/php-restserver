<?php

namespace ByJG\RestServer\Exception;

class Error400Exception extends ClientShowException
{
    public function handleHeader()
    {
        $this->sendHeader(400, 'Bad Request');
    }
}
