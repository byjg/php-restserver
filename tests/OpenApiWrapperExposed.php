<?php

namespace Tests;

use ByJG\RestServer\Route\OpenApiRouteList;

class OpenApiWrapperExposed extends OpenApiRouteList
{
    public function sortPaths($pathList)
    {
        return parent::sortPaths($pathList);
    }
}
