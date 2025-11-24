<?php

namespace ByJG\RestServer\Writer;

use Override;

class HttpWriter implements WriterInterface
{
    #[Override]
    public function header(string $header, bool $replace = true): void
    {
        header($header, $replace);
    }

    #[Override]
    public function responseCode(int $responseCode, string $description): void
    {
        $this->header("HTTP/1.1 $responseCode $description");
    }

    #[Override]
    public function echo(string $data): void
    {
        echo $data;
    }

    #[Override]
    public function flush(): void
    {
        // Do nothing.
    }
}
