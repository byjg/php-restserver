<?php

namespace ByJG\RestServer\Exception;

class Error405Exception extends ClientShowException
{
    /**
     * @return void
     */
    public function handleHeader(): void
    {
        $this->sendHeader(405, 'Method not allowed');
    }
}
