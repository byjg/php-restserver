<?php

namespace ByJG\RestServer\Exception;

class Error406Exception extends ClientShowException
{
    /**
     * @return void
     */
    public function handleHeader(): void
    {
        $this->sendHeader(406, 'Not Acceptable');
    }
}
