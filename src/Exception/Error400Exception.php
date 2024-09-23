<?php

namespace ByJG\RestServer\Exception;

class Error400Exception extends ClientShowException
{
    /**
     * @return void
     */
    public function handleHeader(): void
    {
        $this->sendHeader(400, 'Bad Request');
    }
}
