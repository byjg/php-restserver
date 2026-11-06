<?php

namespace Tests;

use ByJG\RestServer\Route\Route;
use ByJG\RestServer\Route\RouteList;
use PHPUnit\Framework\TestCase;
use Tests\Routes\PlainController;

/**
 * RouteList::getRoute() finds the route a request matches: by its method(s), and by its path as FastRoute reads it
 * (placeholders with or without a pattern, optional segments)
 */
class RouteListGetRouteTest extends TestCase
{
    private function routes(): RouteList
    {
        return (new RouteList())->setRoutes([
            Route::get('/item')->withClass(PlainController::class, 'list'),
            Route::get('/item/{id:\d+}')->withClass(PlainController::class, 'byId'),
            Route::get('/item/{name}')->withClass(PlainController::class, 'byName'),
            Route::get('/archive/{year:\d{4}}/{month:\d{2}}')->withClass(PlainController::class, 'archive'),
            Route::get('/page[/{slug}]')->withClass(PlainController::class, 'page'),
            Route::create(['PUT', 'PATCH'], '/item/{id:\d+}')->withClass(PlainController::class, 'update'),
            Route::create(['PUT', 'PATCH'], '/settings')->withClass(PlainController::class, 'settings'),
        ]);
    }

    private function found(string $method, string $path): ?string
    {
        $class = $this->routes()->getRoute($method, $path)?->getClass();
        return is_array($class) ? $class[1] : null;
    }

    public function testAStaticRoute(): void
    {
        $this->assertSame('list', $this->found('GET', '/item'));
        $this->assertSame('list', $this->found('get', '/item'));
    }

    public function testAPlaceholderWithAPattern(): void
    {
        $this->assertSame('byId', $this->found('GET', '/item/10'));
        // The pattern does not match: the next route does
        $this->assertSame('byName', $this->found('GET', '/item/abc'));
        $this->assertSame('archive', $this->found('GET', '/archive/2024/05'));
        $this->assertNull($this->found('GET', '/archive/24/05'));
    }

    public function testAnOptionalSegment(): void
    {
        $this->assertSame('page', $this->found('GET', '/page'));
        $this->assertSame('page', $this->found('GET', '/page/about'));
    }

    public function testARouteWithSeveralMethods(): void
    {
        $this->assertSame('update', $this->found('PUT', '/item/7'));
        $this->assertSame('update', $this->found('PATCH', '/item/7'));
        $this->assertSame('settings', $this->found('PATCH', '/settings'));
        $this->assertNull($this->found('DELETE', '/item/7'));
    }

    /**
     * The methods of a route are not a regex alternation: PUT matches no path of PUT|PATCH /item/{id}
     */
    public function testAMethodMatchesOnlyThePathOfItsRoute(): void
    {
        $this->assertNull($this->found('PUT', '/anything'));
        $this->assertNull($this->found('GET', '/other'));
    }
}
