<?php

namespace ByJG\RestServer\Exception;

use ByJG\RestServer\HttpResponse;
use Exception;

abstract class HttpResponseException extends Exception
{
    /** @var HttpResponse */
    protected HttpResponse $response;

    protected int $statusCode;

    protected array $meta;

    public function __construct(int $statusCode, string $message = "", int $code = 0, ?\Throwable $previous = null, array $meta = [])
    {
        $this->statusCode = $statusCode;
        $this->meta = $meta;
        parent::__construct($message, $code, $previous);
    }

    protected array $statusCodeList = [
        '400' => 'Bad Request',
        '401' => 'Unauthorized',
        '402' => 'Payment Required',
        '403' => 'Forbidden',
        '404' => 'Not Found',
        '405' => 'Method not allowed',
        '406' => 'Not Acceptable',
        "408" => "Request Timeout",
        '409' => 'Conflict',
        '412' => 'Precondition Failed',
        '415' => 'Unsupported Media Type',
        '422' => 'Unprocessable Entity',
        '429' => 'Too many requests',
        '501' => 'Not Implemented',
        '503' => 'Service Unavailable',
        '520' => 'Unknown Error',
    ];

    public function getMeta(): array
    {
        return $this->meta;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getStatusMessage(): string
    {
        return $this->statusCodeList[$this->getStatusCode()] ?? "Status code not defined";
    }
    public function sendHeader(): void
    {
        $this->response->setResponseCode($this->getStatusCode(), $this->getStatusMessage());
    }

    public function setResponse(HttpResponse $response): void
    {
        $this->response = $response;
    }
}
