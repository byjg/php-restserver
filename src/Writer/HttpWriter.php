<?php

namespace ByJG\RestServer\Writer;

class HttpWriter implements WriterInterface
{
    public function header(string $header, bool $replace = true): void
    {
        header($header, $replace);
    }

    public function responseCode(int $responseCode, string $description): void
    {
        $this->header("HTTP/1.1 $responseCode $description");
        http_response_code($responseCode);
    }

    public function echo(string $data): void
    {
        echo $data;
    }

    public function flush(): void
    {
        // Do nothing.
    }
}
