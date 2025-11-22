<?php

namespace ByJG\RestServer\Exception;

use Throwable;

class ErrorCustomStatusException extends HttpResponseException
{
    public function __construct(int $statusCode, string $statusMessage, string $message = "", int $code = 0, ?Throwable $previous = null, array $meta = [])
    {
        $this->statusCodeList[$statusCode] = $statusMessage;
        parent::__construct($statusCode, $message, $code, $previous, $meta);
    }
}
