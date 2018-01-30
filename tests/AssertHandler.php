<?php

namespace Tests;

use ByJG\RestServer\HandleOutput\JsonHandler;

class AssertHandler extends JsonHandler
{
    /**
     * @param null $headerList
     * @return null|void
     */
    public function writeHeader($headerList = null)
    {
        return null;
    }

    /**
     * @param $data
     * @return mixed|void
     */
    public function writeData($data)
    {
        // Disable the output for test
        return;
    }
}
