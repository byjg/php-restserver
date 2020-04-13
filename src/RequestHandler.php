<?php


namespace ByJG\RestServer;


use ByJG\RestServer\Route\RouteDefinition;

interface RequestHandler
{
    public function handle(RouteDefinition $routeDefinition, $outputBuffer = true, $session = true);
}