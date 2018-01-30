<?php

namespace Tests;

use ByJG\RestServer\ServerRequestHandler;

class ServerRequestHandlerExposed extends ServerRequestHandler
{
    public function sortPaths($pathList)
    {
        return parent::sortPaths($pathList);
    }
}
