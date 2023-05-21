<?php


namespace ByJG\RestServer\Route;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

class RouteList implements RouteListInterface
{
    protected $routes = null;

    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * @param Route[] $routes
     * @return RouteList
     */
    public function setRoutes($routes)
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
    public function addRoute(Route $route)
    {
        if (is_null($this->routes)) {
            $this->routes = [];
        }
        $this->routes[] = $route;
        return $this;
    }

    /**
     * @return Dispatcher
     */
    public function getDispatcher()
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