<?php


namespace ByJG\RestServer\Route;


use FastRoute\Dispatcher;

interface RouteListInterface
{
    /**
     * @return Route[]
     */
    public function getRoutes();

    /**
     * @param Route[] $routes
     */
    public function setRoutes($routes);

    /**
     * @param Route $route
     */
    public function addRoute(Route $route);

    /**
     * @return Dispatcher
     */
    public function getDispatcher();
}
