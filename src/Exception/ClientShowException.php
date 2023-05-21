<?php

namespace ByJG\RestServer\Exception;

use ByJG\RestServer\HttpResponse;
use Exception;

abstract class ClientShowException extends Exception
{
    /** @var HttpResponse */
    protected $response;

    protected function sendHeader($status, $description)
    {
        $this->response->setResponseCode($status, $description);
    }

    public function setResponse(HttpResponse $response)
    {
        $this->response = $response;
    }

    abstract public function handleHeader();
}
