<?php

namespace ByJG\RestServer\Middleware;

enum MiddlewareResult
{
    case stopProcessingOthers;
    case stopProcessing;
    case continue;
}