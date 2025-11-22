<?php

namespace ByJG\RestServer\Exception;

use Throwable;

class Error400Exception extends HttpResponseException
{
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null, array $meta = [])
    {
        parent::__construct(400, $message, $code, $previous, $meta);
    }
}
