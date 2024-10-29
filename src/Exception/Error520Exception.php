<?php

namespace ByJG\RestServer\Exception;

class Error520Exception extends ClientShowException
{
    /**
     * @return void
     */
    public function handleHeader(): void
    {
        $this->sendHeader(520, 'Unknow Error');
    }
}
