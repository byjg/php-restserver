<?php

namespace ByJG\RestServer\Exception;

class Error409Exception extends ClientShowException
{
    /**
     * @return void
     */
    public function handleHeader(): void
    {
        $this->sendHeader(409, 'Conflict');
    }
}
