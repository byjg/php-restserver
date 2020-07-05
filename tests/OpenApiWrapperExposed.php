<?php

namespace Tests;

use ByJG\RestServer\Route\OpenApiRouteDefinition;

class OpenApiWrapperExposed extends OpenApiRouteDefinition
{
    public function sortPaths($pathList)
    {
        return parent::sortPaths($pathList);
    }
}
