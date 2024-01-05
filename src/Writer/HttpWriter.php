<?php

namespace ByJG\RestServer\Writer;

class HttpWriter implements WriterInterface
{
    public function header($header, $replace = true)
    {
        header($header, $replace);
    }

    public function responseCode($responseCode, $description)
    {
        $this->header("HTTP/1.1 $responseCode $description");
        http_response_code($responseCode);
    }

    public function echo($data)
    {
        echo $data;
    }

    public function flush()
    {
        // Do nothing.
    }
}
