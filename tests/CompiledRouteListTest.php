<?php

namespace Tests;

use ByJG\RestServer\MockServer;
use ByJG\RestServer\OutputProcessor\JsonCleanOutputProcessor;
use ByJG\RestServer\OutputProcessor\JsonOutputProcessor;
use ByJG\RestServer\OutputProcessor\XmlOutputProcessor;
use ByJG\RestServer\Route\CompiledRouteList;
use ByJG\RestServer\Route\OpenApiRouteList;
use ByJG\RestServer\Route\Route;
use ByJG\RestServer\Route\RouteList;
use ByJG\RestServer\Route\RouteListInterface;
use ByJG\Util\Uri;
use ByJG\WebRequest\Psr7\Request;
use FastRoute\Dispatcher;
use InvalidArgumentException;
use Override;
use PHPUnit\Framework\TestCase;
use Tests\Routes\PlainController;

/**
 * A route list compiled to a PHP file (CompiledRouteList::compile()) and loaded from it is the same route list:
 * the same routes, dispatched the same way
 */
class CompiledRouteListTest extends TestCase
{
    /** @var string[] */
    private array $files = [];

    #[Override]
    public function tearDown(): void
    {
        array_map(unlink(...), array_filter($this->files, is_file(...)));
    }

    private function compiled(RouteListInterface $routes): CompiledRouteList
    {
        $file = sys_get_temp_dir() . '/routes-' . bin2hex(random_bytes(6)) . '.php';
        $this->files[] = $file;
        file_put_contents($file, CompiledRouteList::compile($routes));
        return new CompiledRouteList($file);
    }

    private function routeList(): RouteList
    {
        return (new RouteList())->setRoutes([
            Route::get('/item')->withOutputProcessor(JsonOutputProcessor::class)->withClass(PlainController::class, 'index'),
            Route::get('/item/{id}')->withOutputProcessor(XmlOutputProcessor::class, true)
                ->withClass(PlainController::class, 'index')->withMetadata(['scope' => 'read', 'roles' => ['a', 'b']]),
            Route::post('/item')->withClass(PlainController::class, 'index'),
            Route::create(['PUT', 'PATCH'], '/item/{id:\d+}')->withOutputProcessor([JsonOutputProcessor::class, XmlOutputProcessor::class])
                ->withClass(PlainController::class, 'index'),
        ]);
    }

    /**
     * @return array<array{string, string}>
     */
    private static function requests(): array
    {
        return [
            ['GET', '/item'], ['GET', '/item/10'], ['POST', '/item'], ['PUT', '/item/7'], ['PATCH', '/item/7'],
            ['PUT', '/item/abc'], ['DELETE', '/item'], ['GET', '/other'], ['GET', '/v2/pet/10'], ['POST', '/v2/pet'],
            ['GET', '/v2/pet/findByStatus'], ['DELETE', '/v2/pet/10'], ['GET', '/v2/store/inventory'],
        ];
    }

    private function assertSameRouteList(RouteListInterface $expected, CompiledRouteList $compiled): void
    {
        $this->assertEquals($expected->getRoutes(), $compiled->getRoutes());
        foreach (self::requests() as [$method, $path]) {
            $this->assertEquals(
                $expected->getDispatcher()->dispatch($method, $path),
                $compiled->getDispatcher()->dispatch($method, $path),
                "$method $path"
            );
        }
    }

    public function testACompiledRouteListIsTheSameRouteList(): void
    {
        $routes = $this->routeList();
        $compiled = $this->compiled($routes);

        $this->assertSameRouteList($routes, $compiled);
        $this->assertEquals(
            [Dispatcher::FOUND, [
                RouteList::META_OUTPUT_PROCESSOR => XmlOutputProcessor::class,
                RouteList::META_CLASS => [PlainController::class, 'index'],
                RouteList::META_OUTPUT_PROCESSOR_STRICT => true,
                'scope' => 'read',
                'roles' => ['a', 'b'],
            ], ['id' => '10']],
            $compiled->getDispatcher()->dispatch('GET', '/item/10')
        );
    }

    public function testACompiledOpenApiRouteListIsTheSameRouteList(): void
    {
        foreach (['/fixtures/swagger-example.json', '/fixtures/openapi-example.yaml'] as $schema) {
            $routes = (new OpenApiRouteList(__DIR__ . $schema))
                ->withOutputProcessorForRoute('get', '/v2/pet/{petId}', JsonCleanOutputProcessor::class);
            $this->assertSameRouteList($routes, $this->compiled($routes));
        }
    }

    public function testGetRoute(): void
    {
        $routes = new OpenApiRouteList(__DIR__ . '/fixtures/openapi-example.yaml');
        $compiled = $this->compiled($routes);

        $this->assertEquals('/v2/pet/{petId}', $compiled->getRoute('GET', '/v2/pet/10')?->getPath());
        $this->assertEquals($routes->getRoute('GET', '/v2/pet/10'), $compiled->getRoute('GET', '/v2/pet/10'));
        $this->assertEquals($routes->getRoute('GET', '/v2/pet/findByTags'), $compiled->getRoute('GET', '/v2/pet/findByTags'));
        $this->assertNull($compiled->getRoute('GET', '/other'));

        // A placeholder with a pattern, a route with several methods
        $routes = $this->routeList();
        $compiled = $this->compiled($routes);
        $this->assertEquals($routes->getRoute('PATCH', '/item/7'), $compiled->getRoute('PATCH', '/item/7'));
        $this->assertEquals('/item/{id:\d+}', $compiled->getRoute('PATCH', '/item/7')?->getPath());
    }

    /**
     * A route changed after it was loaded (the object getRoute() returns) is dispatched as it is now
     */
    public function testAChangedRouteIsDispatchedAsItIsNow(): void
    {
        $compiled = $this->compiled($this->routeList());
        $compiled->getDispatcher()->dispatch('GET', '/item/10');

        $compiled->getRoute('GET', '/item/{id}')->withOutputProcessor(JsonOutputProcessor::class)->withMetadata(['scope' => 'write']);

        $this->assertEquals(
            [Dispatcher::FOUND, [
                RouteList::META_OUTPUT_PROCESSOR => JsonOutputProcessor::class,
                RouteList::META_CLASS => [PlainController::class, 'index'],
                RouteList::META_OUTPUT_PROCESSOR_STRICT => false,
                'scope' => 'write',
            ], ['id' => '10']],
            $compiled->getDispatcher()->dispatch('GET', '/item/10')
        );
    }

    /**
     * A route added to a compiled route list is dispatched with the compiled ones, and replaces the one of the same
     * method and path
     */
    public function testARouteAddedToACompiledRouteList(): void
    {
        $compiled = $this->compiled($this->routeList());
        $compiled->getDispatcher();

        $compiled->addRoute(Route::delete('/item/{id}')->withClass(PlainController::class, 'delete'));
        $compiled->addRoute(Route::post('/item')->withClass(PlainController::class, 'replaced'));

        $expected = $this->routeList()
            ->addRoute(Route::delete('/item/{id}')->withClass(PlainController::class, 'delete'))
            ->addRoute(Route::post('/item')->withClass(PlainController::class, 'replaced'));
        $this->assertSameRouteList($expected, $compiled);
        $this->assertEquals([PlainController::class, 'replaced'], $compiled->getDispatcher()->dispatch('POST', '/item')[1][RouteList::META_CLASS]);
    }

    /**
     * The file is PHP code that returns constant arrays (OPcache keeps them in shared memory), the same for the same
     * routes
     */
    public function testTheFileIsTheSameForTheSameRoutes(): void
    {
        $code = CompiledRouteList::compile($this->routeList());

        $this->assertSame($code, CompiledRouteList::compile($this->routeList()));
        $this->assertStringStartsWith('<?php', $code);
        $this->assertStringNotContainsString('__set_state', $code);
    }

    public function testAServerHandlesARequestWithACompiledRouteList(): void
    {
        $compiled = $this->compiled($this->routeList());

        $response = MockServer::mock($compiled, Request::getInstance(new Uri('http://localhost/item/10'))->withMethod('GET'))
            ->getPsr7Response();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('<?xml version="1.0"?>' . "\n" . '<root><source>new</source></root>' . "\n", (string)$response->getBody());
    }

    public function testARouteToAClosureCannotBeCompiled(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('GET /closure');
        CompiledRouteList::compile((new RouteList())->addRoute(Route::get('/closure')->withClosure(fn() => null)));
    }

    public function testAnObjectInTheMetadataCannotBeCompiled(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('GET /item');
        CompiledRouteList::compile((new RouteList())->addRoute(
            Route::get('/item')->withClass(PlainController::class, 'index')->withMetadata(['object' => new \stdClass()])
        ));
    }

    public function testAFileThatIsNotACompiledRouteList(): void
    {
        $file = sys_get_temp_dir() . '/routes-' . bin2hex(random_bytes(6)) . '.php';
        $this->files[] = $file;
        file_put_contents($file, "<?php\nreturn ['something' => 'else'];\n");

        $this->expectException(InvalidArgumentException::class);
        new CompiledRouteList($file);
    }

    public function testAMissingFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new CompiledRouteList(sys_get_temp_dir() . '/missing-routes.php');
    }
}
