<?php

namespace ByJG\RestServer\Exception;

use Throwable;

class Error422Exception extends HttpResponseException
{
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null, array $meta = [])
    {
        parent::__construct(422, $message, $code, $previous, $meta);
    }
}
