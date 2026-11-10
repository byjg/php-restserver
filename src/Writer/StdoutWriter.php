<?php

namespace ByJG\RestServer\Writer;

use Override;

class StdoutWriter implements WriterInterface
{
    protected array $headerList = [];
    protected string $data = '';
    protected int $statusCode = 0;

    #[Override]
    public function header(string $header, $replace = true): void
    {
        if (preg_match("~^HTTP/~", $header) === 1) {
            array_unshift($this->headerList, $header);
            return;
        }

        // As PHP's header(): replacing removes every line with the same name, compared whole and ignoring
        // case, so X-Foo does not replace X-Foo-Bar
        if ($replace) {
            $name = self::headerName($header);
            $this->headerList = array_values(array_filter(
                $this->headerList,
                static fn(string $line) => strcasecmp(self::headerName($line), $name) !== 0
            ));
        }

        $this->headerList[] = $header;
    }

    private static function headerName(string $line): string
    {
        return trim(explode(':', $line, 2)[0]);
    }

    #[Override]
    public function responseCode(int $responseCode, string $description): void
    {
        $this->header("HTTP/1.1 $responseCode $description");
        $this->statusCode = $responseCode;
    }

    #[Override]
    public function echo(string $data): void
    {
        $this->data .= $data;
    }

    #[Override]
    public function flush(): void
    {
        echo implode("\r\n", $this->headerList);
        echo "\r\n";
        echo "\r\n";
        echo $this->data;
    }
}
