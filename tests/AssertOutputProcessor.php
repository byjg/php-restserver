<?php

namespace Tests;

use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\OutputProcessor\JsonOutputProcessor;
use ByJG\RestServer\OutputProcessor\MockOutputProcessor;

class AssertOutputProcessor extends MockOutputProcessor
{
    protected $output = false;

    public function __construct($output = false)
    {
        $this->output = $output;
        parent::__construct(new JsonOutputProcessor());
    }

    /**
     * @param HttpResponse $headerList
     * @return null|void
     */
    public function writeHeader($headerList = null)
    {
        if ($this->output) {
            parent::writeHeader($headerList);
        }
    }

    /**
     * @param $data
     * @return mixed|void
     */
    public function writeData($data)
    {
        if ($this->output) {
            parent::writeData($data);
        }
    }

    public function writeContentType()
    {
        if ($this->output) {
            parent::writeContentType();
        }
    }
}
