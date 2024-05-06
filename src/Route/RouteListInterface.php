<?php


namespace ByJG\RestServer\Route;


use FastRoute\Dispatcher;

interface RouteListInterface
{
    /**
     * @return Route[]
     */
    public function getRoutes(): array;

    /**
     * @param Route[] $routes
     */
    public function setRoutes(array $routes): static;

    /**
     * @param Route $route
     */
    public function addRoute(Route $route): static;

    public function addClass(string $className): static;

    /**
     * @return Dispatcher
     */
    public function getDispatcher(): Dispatcher;
}
