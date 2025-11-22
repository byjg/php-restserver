<?php

namespace ByJG\RestServer\Exception;

use Throwable;

class Error429Exception extends HttpResponseException
{
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null, array $meta = [])
    {
        parent::__construct(429, $message, $code, $previous, $meta);
    }
}
