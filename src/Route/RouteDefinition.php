<?php


namespace ByJG\RestServer\Route;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

class RouteDefinition implements RouteDefinitionInterface
{
    protected $routes = null;

    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * @param RoutePattern[] $routes
     * @return RouteDefinition
     */
    public function setRoutes($routes)
    {
        foreach ((array)$routes as $route) {
            $this->addRoute($route);
        }
        return $this;
    }

    /**
     * @param RoutePattern $route
     * @return RouteDefinition
     */
    public function addRoute(RoutePattern $route)
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
                    $route->getPattern(),
                    [
                        "output_processor" => $route->getOutputProcessor(),
                        "class" => $route->getClass(),
                    ]
                );
            }
        });
    }

}