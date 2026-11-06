<?php


namespace ByJG\RestServer\Route;

use ByJG\RestServer\Attributes\RouteDefinition;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
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
     * The dispatcher of the routes, built once: null until it is used, and again when a route is added
     */
    private ?Dispatcher $dispatcher = null;

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
        $this->routes[static::routeKey($route)] = $route;
        $this->dispatcher = null;
        return $this;
    }

    /**
     * The key of a route in the list: its method(s) and path. A route added with the key of another replaces it.
     */
    protected static function routeKey(Route $route): string
    {
        $method = $route->getMethod();
        $methodStr = is_array($method) ? implode('|', $method) : $method;
        return strtoupper($methodStr) . " " . strtolower($route->getPath());
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
     * The dispatcher of the routes. It is built once, until a route is added, instead of on each request: only the
     * method and the path of a route, which do not change, are compiled. The route metadata of a match is read from
     * the Route when dispatched, so a route changed after (withOutputProcessor(), withMetadata()...) is dispatched as
     * it is now.
     *
     * @return Dispatcher
     */
    #[Override]
    public function getDispatcher(): Dispatcher
    {
        return $this->dispatcher ??= new RouteDispatcher(
            simpleDispatcher(function (RouteCollector $r) {
                foreach ($this->getRoutes() as $route) {
                    $r->addRoute($route->getMethod(), $route->getPath(), $route);
                }
            }),
            $this->getRouteMetadata(...)
        );
    }

    /**
     * The route metadata of a route, as a match gives it: its output processor, class and own metadata
     */
    protected function getRouteMetadata(Route $route): array
    {
        return array_merge([
            self::META_OUTPUT_PROCESSOR => $route->getOutputProcessor(),
            self::META_CLASS => $route->getClass(),
            self::META_OUTPUT_PROCESSOR_STRICT => $route->isOutputContentTypeStrict()
        ], $route->getMetadata());
    }

    /**
     * The route a request matches: by its method(s), and by its path as FastRoute reads it (placeholders with or
     * without a pattern, optional segments). The path is compared in any case.
     */
    #[Override]
    public function getRoute(string $method, string $path): ?Route
    {
        $method = strtoupper($method);
        $pathMethod = $method . " " . strtolower($path);
        $route = $this->routes[$pathMethod] ?? null;
        if (!empty($route)) {
            return $route;
        }

        $parser = new Std();
        foreach ($this->routes as $route) {
            if (!in_array($method, array_map(strtoupper(...), (array)$route->getMethod()), true)) {
                continue;
            }
            // One variant for each optional segment; a part is text, or a placeholder [name, pattern]
            foreach ($parser->parse($route->getPath()) as $parts) {
                $regex = implode('', array_map(
                    fn(string|array $part): string => is_string($part) ? preg_quote($part, '~') : "($part[1])",
                    $parts
                ));
                if (preg_match("~^$regex\$~i", $path) === 1) {
                    return $route;
                }
            }
        }

        return null;
    }
}