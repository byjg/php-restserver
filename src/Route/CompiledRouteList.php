<?php

namespace ByJG\RestServer\Route;

use Closure;
use FastRoute\DataGenerator\GroupCountBased as GroupCountBasedGenerator;
use FastRoute\Dispatcher;
use FastRoute\Dispatcher\GroupCountBased;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use InvalidArgumentException;
use Override;

/**
 * A route list loaded from a PHP file that compile() wrote: the routes as arrays, and FastRoute's dispatch data.
 *
 * The file returns constant arrays: OPcache keeps them in shared memory, so loading the routes and the dispatcher
 * costs almost nothing in a web request, where a route list is built again for each request. A Route object is
 * created only for the route a request matches (or when the routes are read), and it is the route from then on:
 * a change to it (getRoute()->withOutputProcessor()...) is used by the next dispatch. A route added afterwards is
 * dispatched with the others (the dispatcher is then built from the routes, as RouteList does).
 */
class CompiledRouteList extends RouteList
{
    /**
     * @var array<string, array{0: string|array, 1: string, 2: string|array|null, 3: bool, 4: array|null, 5: array}>
     *     The routes by key: method, path, output processor, strict, class and method, metadata
     */
    private array $compiledRoutes;

    /**
     * @var array|null FastRoute's dispatch data, with the route key as handler; null once a route was added
     */
    private ?array $dispatchData;

    private ?Dispatcher $compiledDispatcher = null;

    private bool $loaded = false;

    /**
     * @param string $filename A file compile() wrote
     */
    public function __construct(string $filename)
    {
        if (!is_file($filename)) {
            throw new InvalidArgumentException("The compiled routes '$filename' were not found");
        }
        $compiled = require $filename;
        if (!is_array($compiled) || !is_array($compiled['routes'] ?? null) || !is_array($compiled['dispatch'] ?? null)) {
            throw new InvalidArgumentException("The file '$filename' has no compiled routes");
        }
        $this->compiledRoutes = $compiled['routes'];
        $this->dispatchData = $compiled['dispatch'];
    }

    /**
     * The PHP code of the file of a route list, for the constructor. A route must run a class method (a closure
     * cannot be written to a file), and its metadata can have only scalars and arrays.
     *
     * @throws InvalidArgumentException when a route cannot be compiled
     */
    public static function compile(RouteListInterface $routeList): string
    {
        $routes = [];
        $collector = new RouteCollector(new Std(), new GroupCountBasedGenerator());
        foreach ($routeList->getRoutes() as $route) {
            $key = static::routeKey($route);
            $class = $route->getClass();
            if ($class !== null && !is_array($class)) {
                $what = $class instanceof Closure ? 'a closure' : 'no class method';
                throw new InvalidArgumentException("The route $key runs $what: only a class method can be compiled");
            }
            $metadata = $route->getMetadata();
            array_walk_recursive($metadata, function (mixed $value) use ($key): void {
                if (is_object($value) || is_resource($value)) {
                    throw new InvalidArgumentException("The metadata of the route $key has an object: only scalars and arrays can be compiled");
                }
            });

            $routes[$key] = [
                $route->getMethod(),
                $route->getPath(),
                $route->getOutputProcessor(),
                $route->isOutputContentTypeStrict(),
                $class,
                $metadata,
            ];
            $collector->addRoute($route->getMethod(), $route->getPath(), $key);
        }

        return "<?php\n\n// The compiled routes (ByJG\\RestServer\\Route\\CompiledRouteList::compile()). Do not edit.\n\n"
            . 'return ' . var_export(['routes' => $routes, 'dispatch' => $collector->getData()], true) . ";\n";
    }

    #[Override]
    public function getRoutes(): array
    {
        $this->load();
        return parent::getRoutes();
    }

    #[Override]
    public function addRoute(Route $route): static
    {
        // The compiled dispatch data does not have it: from now on the dispatcher is built from the routes
        $this->load();
        $this->dispatchData = null;
        $this->compiledDispatcher = null;
        return parent::addRoute($route);
    }

    #[Override]
    public function getRoute(string $method, string $path): ?Route
    {
        $this->load();
        return parent::getRoute($method, $path);
    }

    #[Override]
    public function getDispatcher(): Dispatcher
    {
        if ($this->dispatchData === null) {
            return parent::getDispatcher();
        }

        return $this->compiledDispatcher ??= new RouteDispatcher(
            new GroupCountBased($this->dispatchData),
            fn(string $key): array => $this->getRouteMetadata($this->route($key))
        );
    }

    /**
     * The Route of a key: created the first time from the compiled route, the same object afterwards
     */
    private function route(string $key): Route
    {
        if (!isset($this->routes[$key])) {
            [$method, $path, $outputProcessor, $strict, $class, $metadata] = $this->compiledRoutes[$key];
            $route = Route::create($method, $path)->withMetadata($metadata);
            if ($outputProcessor !== null) {
                $route->withOutputProcessor($outputProcessor, $strict);
            }
            if ($class !== null) {
                $route->withClass($class[0], $class[1]);
            }
            // Not addRoute(): the compiled dispatch data still has it
            $this->routes[$key] = $route;
        }
        return $this->routes[$key];
    }

    /**
     * Every compiled route as a Route, in the compiled order
     */
    private function load(): void
    {
        if ($this->loaded) {
            return;
        }
        $routes = [];
        foreach (array_keys($this->compiledRoutes) as $key) {
            $routes[$key] = $this->route($key);
        }
        $this->routes = $routes;
        $this->loaded = true;
    }
}
