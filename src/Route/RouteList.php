<?php


namespace ByJG\RestServer\Route;

use ByJG\RestServer\Attributes\RouteDefinition;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use function FastRoute\simpleDispatcher;

class RouteList implements RouteListInterface
{
    protected array $routes = [];

    /**
     * @return Route[]
     */
    public function getRoutes(): array
    {
        return array_values($this->routes);
    }

    /**
     * @param Route[] $routes
     * @return static
     */
    public function setRoutes(array $routes): static
    {
        foreach ($routes as $route) {
            $this->addRoute($route);
        }
        return $this;
    }

    /**
     * @param Route $route
     * @return static
     */
    public function addRoute(Route $route): static
    {
        $this->routes[strtoupper($route->getMethod()) . " " . strtolower($route->getPath())] = $route;
        return $this;
    }

    /**
     * @throws ReflectionException
     */
    public function addClass(string $className): static
    {
        $reflection = new ReflectionClass($className);
        $methods = $reflection->getMethods();

        foreach ($methods as $method) {
            $attributes = $method->getAttributes(RouteDefinition::class, ReflectionAttribute::IS_INSTANCEOF);

            foreach ($attributes as $attribute) {
                $this->addRoute($attribute->newInstance()->createRoute($className, $method->getName()));
            }
        }

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

    public function getRoute(string $method, string $path): ?Route
    {
        return $this->routes[strtoupper($method) . " " . strtolower($path)] ?? null;
    }
}