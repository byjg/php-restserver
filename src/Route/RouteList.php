<?php


namespace ByJG\RestServer\Route;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

class RouteList implements RouteListInterface
{
    protected array $routes = [];

    /**
     * @return Route[]
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * @param Route[] $routes
     * @return RouteList
     */
    public function setRoutes(array $routes): static
    {
        foreach ((array)$routes as $route) {
            $this->addRoute($route);
        }
        return $this;
    }

    /**
     * @param Route $route
     * @return RouteList
     */
    public function addRoute(Route $route): static
    {
        $this->routes[] = $route;
        return $this;
    }

    /**
     * @return Dispatcher
     */
    public function getDispatcher(): Dispatcher
    {
        // Generic Dispatcher for RestServer
        return simpleDispatcher(function (RouteCollector $r) {

            foreach ($this->getRoutes() as $route) {
                $r->addRoute(
                    $route->getMethod(),
                    $route->getPath(),
                    [
                        "output_processor" => $route->getOutputProcessor(),
                        "class" => $route->getClass(),
                    ]
                );
            }
        });
    }

}