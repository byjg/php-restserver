<?php

namespace ByJG\RestServer\Route;

use Closure;
use FastRoute\Dispatcher;
use Override;

/**
 * The dispatcher of a RouteList: FastRoute's, compiled once, with a handler that identifies the route (the Route
 * object, or its key in a CompiledRouteList). A match gives the route metadata of the Route as it is when dispatched
 * (RouteList::getDispatcher()), not as it was when compiled.
 */
class RouteDispatcher implements Dispatcher
{
    /**
     * @param Dispatcher $dispatcher FastRoute's dispatcher
     * @param Closure(mixed): array $metadata The route metadata of the route of a handler
     */
    public function __construct(
        private readonly Dispatcher $dispatcher,
        private readonly Closure $metadata,
    ) {
    }

    #[Override]
    public function dispatch($httpMethod, $uri): array
    {
        $routeInfo = $this->dispatcher->dispatch($httpMethod, $uri);
        if ($routeInfo[0] === self::FOUND) {
            $routeInfo[1] = ($this->metadata)($routeInfo[1]);
        }
        return $routeInfo;
    }
}
