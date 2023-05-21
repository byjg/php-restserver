<?php

namespace ByJG\RestServer\Writer;

class MemoryWriter extends StdoutWriter
{
    public function flush()
    {
        // Do nothing
        return null;
    }

    public function getData()
    {
        return $this->data;
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