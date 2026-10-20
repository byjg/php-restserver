<?php

namespace ByJG\RestServer\Middleware;

enum MiddlewareResult
{
    case StopProcessingOthers;
    case StopProcessing;
    case Continue;
}