<?php

namespace ByJG\RestServer\Exception;

class Error501Exception extends ClientShowException
{
    /**
     * @return void
     */
    public function handleHeader(): void
    {
        $this->sendHeader(501, 'Not Implemented');
    }
}
