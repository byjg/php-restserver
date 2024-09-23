<?php

namespace ByJG\RestServer\Exception;

class Error401Exception extends ClientShowException
{
    /**
     * @return void
     */
    public function handleHeader(): void
    {
        $this->sendHeader(401, 'Unauthorized');
    }
}
