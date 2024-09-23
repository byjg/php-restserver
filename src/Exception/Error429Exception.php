<?php

namespace ByJG\RestServer\Exception;

class Error429Exception extends ClientShowException
{
    /**
     * @return void
     */
    public function handleHeader(): void
    {
        $this->sendHeader(429, 'Too many requests');
    }
}
