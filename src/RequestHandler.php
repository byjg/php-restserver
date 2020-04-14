<?php


namespace ByJG\RestServer;


use ByJG\RestServer\Route\RouteDefinitionInterface;

interface RequestHandler
{
    public function handle(RouteDefinitionInterface $routeDefinition, $outputBuffer = true, $session = true);
}