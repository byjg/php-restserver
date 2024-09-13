<?php

namespace ByJG\RestServer\Exception;

class Error415Exception extends ClientShowException
{
    /**
     * @return void
     */
    public function handleHeader(): void
    {
        $this->sendHeader(415, 'Unsupported Media Type');
    }
}
