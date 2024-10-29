<?php

namespace ByJG\RestServer\Exception;

class Error404Exception extends ClientShowException
{
    /**
     * @return void
     */
    public function handleHeader(): void
    {
        $this->sendHeader(404, 'Not Found');
    }
}
