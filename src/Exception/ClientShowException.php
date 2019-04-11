<?php

namespace ByJG\RestServer\Exception;

use Exception;

abstract class ClientShowException extends Exception
{
    //put your code here

    protected function sendHeader($status, $description)
    {
        header("HTTP/1.0 $status $description", true, $status);
        http_response_code($status);
    }

    abstract public function handleHeader();
}
