<?php

namespace Tests;

use ByJG\RestServer\Route\SwaggerRouteDefinition;

class SwaggerWrapperExposed extends SwaggerRouteDefinition
{
    public function sortPaths($pathList)
    {
        return parent::sortPaths($pathList);
    }
}
