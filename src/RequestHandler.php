<?php


namespace ByJG\RestServer;


use ByJG\RestServer\Route\RouteListInterface;

interface RequestHandler
{
    public function handle(RouteListInterface $routeDefinition, $outputBuffer = true, $session = true);

    public function withDoNotUseErrorHandler();

    public function withDetailedErrorHandler();
}