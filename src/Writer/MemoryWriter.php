<?php

namespace ByJG\RestServer\Writer;

class MemoryWriter extends StdoutWriter
{

    public function flush(): void
    {
        // Do nothing
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function responseCode(int $responseCode, string $description): void
    {
        $this->header("HTTP/1.1 $responseCode $description");
        $this->statusCode = $responseCode;
    }

    public function getHeaders(): array
    {
        return $this->headerList;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}