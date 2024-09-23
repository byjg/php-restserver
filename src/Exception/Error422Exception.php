<?php

namespace ByJG\RestServer\Exception;

class Error422Exception extends ClientShowException
{
    /**
     * @return void
     */
    public function handleHeader(): void
    {
        $this->sendHeader(422, 'Unprocessable Entity');
    }
}
