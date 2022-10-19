<?php


namespace ByJG\RestServer;


use ByJG\RestServer\Route\RouteListInterface;

interface RequestHandler
{
    public function handle(RouteListInterface $routeDefinition, $outputBuffer = true, $session = true);

    public function withCorsDisabled();

    public function withErrorHandlerDisabled();

    public function withDetailedErrorHandler();

    public function withCorsOrigins($origins);

    public function withAcceptCorsHeaders($headers);

    public function withAcceptCorsMethods($methods);

    public function withDefaultOutputProcessor($processor, $args = []);
}