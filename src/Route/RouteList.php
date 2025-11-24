<?php


namespace ByJG\RestServer\Route;

use ByJG\RestServer\Attributes\RouteDefinition;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Override;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use function FastRoute\simpleDispatcher;

class RouteList implements RouteListInterface
{
    const string META_OUTPUT_PROCESSOR = 'output_processor';
    const string META_CLASS = 'class';
    const string META_OUTPUT_PROCESSOR_STRICT = 'output_processor_strict';

    protected array $routes = [];

    /**
     * @return Route[]
     */
    #[Override]
    public function getRoutes(): array
    {
        return array_values($this->routes);
    }

    /**
     * @param Route[] $routes
     * @return static
     */
    #[Override]
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
    #[Override]
    public function addRoute(Route $route): static
    {
        $method = $route->getMethod();
        $methodStr = is_array($method) ? implode('|', $method) : $method;
        $this->routes[strtoupper($methodStr) . " " . strtolower($route->getPath())] = $route;
        return $this;
    }

    /**
     * @param class-string $className
     * @throws ReflectionException
     */
    #[Override]
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
    #[Override]
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
                        self::META_OUTPUT_PROCESSOR_STRICT => $route->isOutputContentTypeStrict()
                    ], $route->getMetadata())
                );
            }
        });
    }

    #[Override]
    public function getRoute(string $method, string $path): ?Route
    {
        $pathMethod = strtoupper($method) . " " . strtolower($path);
        $route = $this->routes[$pathMethod] ?? null;
        if (!empty($route)) {
            return $route;
        }

        foreach (array_keys($this->routes) as $pathItem) {
            if (!is_string($pathItem) || !str_contains($pathItem, '{')) {
                continue;
            }

            $replacedPath = preg_replace('~{(.*?)}~', '(?<\1>[^/]+)', $pathItem);
            $pathItemPattern = '~^' . (is_string($replacedPath) ? $replacedPath : $pathItem) . '$~';

            if (preg_match($pathItemPattern, $pathMethod, $matches)) {
                return $this->routes[$pathItem] ?? null;
            }
        }

        return null;
    }
}