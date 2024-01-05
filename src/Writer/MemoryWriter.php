<?php

namespace ByJG\RestServer\Writer;

class MemoryWriter extends StdoutWriter
{

    public function flush()
    {
        // Do nothing
    }

    public function getData()
    {
        return $this->data;
    }

    public function responseCode($responseCode, $description)
    {
        $this->header("HTTP/1.1 $responseCode $description");
        $this->statusCode = $responseCode;
    }

    public function getHeaders()
    {
        return $this->headerList;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }
}