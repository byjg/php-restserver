<?php

namespace Tests;

use ByJG\RestServer\HandleOutput\JsonHandler;

class AssertHandler extends JsonHandler
{
    public function writeHeader($headerList = null)
    {
        return null;
    }

    public function writeData($data)
    {
        // Disable the output for test
        return;
    }
}
