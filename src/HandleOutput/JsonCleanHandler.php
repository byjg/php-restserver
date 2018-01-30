<?php

namespace ByJG\RestServer\HandleOutput;

class JsonCleanHandler extends JsonHandler
{
    public function __construct()
    {
        $this->buildNull = false;
        $this->onlyString = false;
        parent::__construct();
    }
}
