<?php

namespace ByJG\RestServer\HandleOutput;

class JsonHandlerClean extends JsonHandler
{
    public function __construct()
    {
        $this->option('build-null', false);
        $this->option('only-string', false);
        parent::__construct();
    }
}
