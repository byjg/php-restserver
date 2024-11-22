<?php

namespace ByJG\RestServer\Exception;

class Error415Exception extends HttpResponseException
{
    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null, array $meta = [])
    {
        parent::__construct(415, $message, $code, $previous, $meta);
    }
}
