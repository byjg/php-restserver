<?php

namespace ByJG\RestServer\Exception;

class Error402Exception extends ClientShowException
{
    /**
     * @return void
     */
    public function handleHeader(): void
    {
        $this->sendHeader(402, 'Payment Required');
    }
}
