<?php

namespace ByJG\RestServer\Exception;

use Throwable;

class ErrorCustomStatusException extends ClientShowException
{
    protected $status;
    protected $description;

    public function __construct($message = "", $code = 400, Throwable $previous = null)
    {
        $this->status = $code;
        $this->description = $message;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return void
     */
    public function handleHeader(): void
    {
        $this->sendHeader($this->status, $this->description);
    }
}
