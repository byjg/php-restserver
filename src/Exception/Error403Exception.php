<?php

namespace ByJG\RestServer\Exception;

class Error403Exception extends ClientShowException
{
    /**
     * @return void
     */
    public function handleHeader(): void
    {
        $this->sendHeader(403, 'Forbidden');
    }
}
