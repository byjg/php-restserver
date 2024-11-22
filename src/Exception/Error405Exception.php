<?php

namespace ByJG\RestServer\Exception;

class Error405Exception extends HttpResponseException
{
    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null, array $meta = [])
    {
        parent::__construct(405, $message, $code, $previous, $meta);
    }
}
