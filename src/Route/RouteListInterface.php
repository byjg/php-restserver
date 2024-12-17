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
     * @param string $method
     * @param string $path
     * @return Route|null
     */
    public function getRoute(string $method, string $path): ?Route;

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
