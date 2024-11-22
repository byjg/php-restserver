<?php

namespace ByJG\RestServer\Exception;

class Error408Exception extends HttpResponseException
{
    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null, array $meta = [])
    {
        parent::__construct(408, $message, $code, $previous, $meta);
    }
}
