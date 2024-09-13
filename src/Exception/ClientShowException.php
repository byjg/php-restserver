<?php

namespace ByJG\RestServer\Exception;

use ByJG\RestServer\HttpResponse;
use Exception;

abstract class ClientShowException extends Exception
{
    /** @var HttpResponse */
    protected HttpResponse $response;

    protected function sendHeader(int $status, string $description): void
    {
        $this->response->setResponseCode($status, $description);
    }

    public function setResponse(HttpResponse $response): void
    {
        $this->response = $response;
    }

    abstract public function handleHeader(): void;
}
