<?php

namespace Tests;

use ByJG\RestServer\SwaggerWrapper;

class SwaggerWrapperExposed extends SwaggerWrapper
{
    public function sortPaths($pathList)
    {
        return parent::sortPaths($pathList);
    }
}
