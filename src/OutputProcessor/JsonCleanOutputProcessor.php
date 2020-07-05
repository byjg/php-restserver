<?php

namespace ByJG\RestServer\OutputProcessor;

class JsonCleanOutputProcessor extends JsonOutputProcessor
{
    public function __construct()
    {
        $this->buildNull = false;
        $this->onlyString = false;
        parent::__construct();
    }
}
