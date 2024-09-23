<?php

namespace ByJG\RestServer\Writer;

interface WriterInterface
{
    public function header(string $header, bool $replace = true): void;

    public function responseCode(int $responseCode, string $description): void;

    public function echo(string $data): void;

    public function flush(): void;
}
