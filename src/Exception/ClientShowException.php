<?php

namespace ByJG\RestServer\Exception;

abstract class ClientShowException extends \Exception
{
    //put your code here

    protected function sendHeader($status, $description)
    {
        header("HTTP/1.0 $status $description", true, $status);
    }

    abstract public function handleHeader();
}
