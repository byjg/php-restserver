<?php

namespace Tests;

use ByJG\RestServer\OutputProcessor\JsonOutputProcessor;
use ByJG\RestServer\OutputProcessor\XmlOutputProcessor;
use ByJG\RestServer\Route\Route;
use ByJG\RestServer\Route\RouteList;
use FastRoute\Dispatcher;
use PHPUnit\Framework\TestCase;

/**
 * What RouteList::getDispatcher() matches, and the route metadata a match gives (the output processor, the class
 * and the route's own metadata), always the route's current ones
 */
class RouteListDispatcherTest extends TestCase
{
    private function routeList(): RouteList
    {
        return (new RouteList())->setRoutes([
            Route::get('/item/{id}')->withOutputProcessor(JsonOutputProcessor::class)->withClass(self::class, 'get')
                ->withMetadata(['scope' => 'read']),
            Route::post('/item')->withOutputProcessor(XmlOutputProcessor::class, true)->withClass(self::class, 'post'),
        ]);
    }

    public function testDispatch(): void
    {
        $dispatcher = $this->routeList()->getDispatcher();

        $this->assertEquals(
            [Dispatcher::FOUND, [
                RouteList::META_OUTPUT_PROCESSOR => JsonOutputProcessor::class,
                RouteList::META_CLASS => [self::class, 'get'],
                RouteList::META_OUTPUT_PROCESSOR_STRICT => false,
                'scope' => 'read',
            ], ['id' => '10']],
            $dispatcher->dispatch('GET', '/item/10')
        );
        $this->assertEquals(
            [Dispatcher::FOUND, [
                RouteList::META_OUTPUT_PROCESSOR => XmlOutputProcessor::class,
                RouteList::META_CLASS => [self::class, 'post'],
                RouteList::META_OUTPUT_PROCESSOR_STRICT => true,
            ], []],
            $dispatcher->dispatch('POST', '/item')
        );
        $this->assertEquals([Dispatcher::NOT_FOUND], $dispatcher->dispatch('GET', '/other'));
        $this->assertEquals([Dispatcher::METHOD_NOT_ALLOWED, ['POST']], $dispatcher->dispatch('PUT', '/item'));
    }

    /**
     * The dispatcher is built once (a request no longer rebuilds it) until a route is added
     */
    public function testTheDispatcherIsBuiltOnceUntilARouteIsAdded(): void
    {
        $routes = $this->routeList();
        $dispatcher = $routes->getDispatcher();
        $this->assertSame($dispatcher, $routes->getDispatcher());

        $routes->addRoute(Route::delete('/item/{id}')->withClass(self::class, 'delete'));
        $this->assertNotSame($dispatcher, $routes->getDispatcher());
        $this->assertEquals(Dispatcher::FOUND, $routes->getDispatcher()->dispatch('DELETE', '/item/3')[0]);
        $this->assertEquals([self::class, 'delete'], $routes->getDispatcher()->dispatch('DELETE', '/item/3')[1][RouteList::META_CLASS]);
    }

    /**
     * A route changed after a request (it is the object getRoute() returns) gives its new values to the next one
     */
    public function testAChangedRouteIsDispatchedAsItIsNow(): void
    {
        $routes = $this->routeList();
        $this->assertEquals(JsonOutputProcessor::class, $routes->getDispatcher()->dispatch('GET', '/item/1')[1][RouteList::META_OUTPUT_PROCESSOR]);

        $routes->getRoute('GET', '/item/{id}')
            ->withOutputProcessor(XmlOutputProcessor::class, true)
            ->withClass(self::class, 'other')
            ->withMetadata(['scope' => 'write']);

        $this->assertEquals(
            [Dispatcher::FOUND, [
                RouteList::META_OUTPUT_PROCESSOR => XmlOutputProcessor::class,
                RouteList::META_CLASS => [self::class, 'other'],
                RouteList::META_OUTPUT_PROCESSOR_STRICT => true,
                'scope' => 'write',
            ], ['id' => '1']],
            $routes->getDispatcher()->dispatch('GET', '/item/1')
        );
    }

    /**
     * Adding a route with the method and path of another replaces it
     */
    public function testARouteAddedAgainReplacesTheOther(): void
    {
        $routes = $this->routeList();
        $routes->getDispatcher();

        $routes->addRoute(Route::get('/item/{id}')->withClass(self::class, 'replaced'));

        $this->assertEquals([self::class, 'replaced'], $routes->getDispatcher()->dispatch('GET', '/item/1')[1][RouteList::META_CLASS]);
    }
}
