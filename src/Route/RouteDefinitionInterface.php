<?php


namespace ByJG\RestServer\Route;


use FastRoute\Dispatcher;

interface RouteDefinitionInterface
{
    /**
     * @return RoutePattern[]
     */
    public function getRoutes();

    /**
     * @param RoutePattern[] $routes
     */
    public function setRoutes($routes);

    /**
     * @param RoutePattern $route
     */
    public function addRoute(RoutePattern $route);

    /**
     * @return Dispatcher
     */
    public function getDispatcher();
}
