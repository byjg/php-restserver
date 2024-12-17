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
    const META_OUTPUT_PROCESSOR = 'output_processor';
    const META_CLASS = 'class';

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
                    array_merge([
                        self::META_OUTPUT_PROCESSOR => $route->getOutputProcessor(),
                        self::META_CLASS => $route->getClass(),
                    ], $route->getMetadata())
                );
            }
        });
    }

    public function getRoute(string $method, string $path): ?Route
    {
        $pathMethod = strtoupper($method) . " " . strtolower($path);
        $route = $this->routes[$pathMethod] ?? null;
        if (!empty($route)) {
            return $route;
        }

        foreach (array_keys($this->routes) as $pathItem) {
            if (!str_contains($pathItem, '{')) {
                continue;
            }

            $pathItemPattern = '~^' . preg_replace('~{(.*?)}~', '(?<\1>[^/]+)', $pathItem) . '$~';

            if (preg_match($pathItemPattern, $pathMethod, $matches)) {
                return $this->routes[$pathItem] ?? null;
            }
        }

        return null;
    }
}