<?php

namespace Tests;

use ByJG\RestServer\Route\OpenApiRouteList;

class OpenApiWrapperExposed extends OpenApiRouteList
{
    public function sortPaths(array $pathList): array
    {
        return parent::sortPaths($pathList);
    }
}
