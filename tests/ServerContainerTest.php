<?php

namespace Tests;

use ByJG\RestServer\ErrorHandler;
use ByJG\RestServer\Exception\ControllerNotRegisteredException;
use ByJG\RestServer\Exception\InvalidClassException;
use ByJG\RestServer\OutputProcessor\JsonOutputProcessor;
use ByJG\RestServer\Route\Route;
use ByJG\RestServer\Route\RouteList;
use ByJG\RestServer\Server;
use ByJG\RestServer\Writer\MemoryWriter;
use Closure;
use Exception;
use Override;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\Routes\ContainerGreeter;
use Tests\Routes\InjectedController;
use Tests\Routes\PlainController;

class ServerContainerTest extends TestCase
{
    protected RouteList $definition;
    protected MemoryWriter $writer;

    #[Override]
    public function setUp(): void
    {
        ini_set('output_buffering', 4096);

        $this->writer = new MemoryWriter();
        $this->definition = new RouteList();
        $this->definition->addRoute(Route::get('/plain')->withClass(PlainController::class, 'index'));
        $this->definition->addRoute(Route::get('/injected')->withClass(InjectedController::class, 'index'));
        $this->definition->addRoute(Route::get('/attributes')->withClass(InjectedController::class, 'withAttributes'));
    }

    #[Override]
    public function tearDown(): void
    {
        $_SERVER = [];
        ErrorHandler::getInstance()->unregister();
    }

    /**
     * A minimal PSR-11 implementation, so the test adds no dev dependency. Closure
     * entries are invoked on get(), which lets a binding build an object with arguments.
     */
    protected function container(array $entries): ContainerInterface
    {
        return new class ($entries) implements ContainerInterface {
            public function __construct(protected array $entries)
            {
            }

            public function get(string $id): mixed
            {
                if (!$this->has($id)) {
                    throw new class ("Entry '$id' not found") extends Exception implements NotFoundExceptionInterface {
                    };
                }

                $entry = $this->entries[$id];
                return $entry instanceof Closure ? $entry() : $entry;
            }

            public function has(string $id): bool
            {
                return array_key_exists($id, $this->entries);
            }
        };
    }

    /**
     * @throws Exception
     */
    protected function dispatch(Server $server, string $path): string
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "http://localhost$path";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        try {
            $server
                ->withDefaultOutputProcessor(JsonOutputProcessor::class)
                ->withWriter($this->writer);

            $server->handle($this->definition, true, false);
        } finally {
            ob_clean();
            ob_end_flush();
        }

        return $this->writer->getData();
    }

    /**
     * The legacy path: with no container the Server keeps using `new $className()`.
     * This is the regression guard for every existing application.
     *
     * @throws Exception
     */
    public function testWithoutContainerStillInstantiatesDirectly(): void
    {
        $server = new Server();

        $this->assertEquals('{"source":"new"}', $this->dispatch($server, '/plain'));
    }

    /**
     * InjectedController has no zero-argument constructor, so a successful response is
     * only possible if the container built it.
     *
     * @throws Exception
     */
    public function testRegisteredControllerIsResolvedFromContainer(): void
    {
        $server = (new Server())->withContainer($this->container([
            InjectedController::class => fn() => new InjectedController(new ContainerGreeter('injected')),
        ]));

        $this->assertEquals('{"greeting":"injected"}', $this->dispatch($server, '/injected'));
    }

    /**
     * Strict by default: setting a container changes how controllers are built, so an
     * unregistered controller must fail loudly instead of being silently constructed
     * without its dependencies.
     *
     * @throws Exception
     */
    public function testUnregisteredControllerThrowsWhenStrict(): void
    {
        $server = (new Server())->withContainer($this->container([]));

        $this->expectException(ControllerNotRegisteredException::class);
        $this->expectExceptionMessage("Controller '" . PlainController::class . "' is not registered in the container");

        $this->dispatch($server, '/plain');
    }

    /**
     * The migration escape hatch, which has to be asked for explicitly.
     *
     * @throws Exception
     */
    public function testUnregisteredControllerFallsBackWhenAllowed(): void
    {
        $server = (new Server())->withContainer($this->container([]), allowUnregistered: true);

        $this->assertEquals('{"source":"new"}', $this->dispatch($server, '/plain'));
    }

    /**
     * A binding can return anything. Without the instanceof guard this surfaces as a
     * confusing "no method" error rather than naming the real cause.
     *
     * @throws Exception
     */
    public function testContainerReturningWrongTypeThrows(): void
    {
        $server = (new Server())->withContainer($this->container([
            PlainController::class => new ContainerGreeter('not a controller'),
        ]));

        $this->expectException(InvalidClassException::class);
        $this->expectExceptionMessage('The container returned ' . ContainerGreeter::class . " for '" . PlainController::class . "'");

        $this->dispatch($server, '/plain');
    }

    /**
     * Before/after route attributes are processed on the instance the Server built, so
     * they must still fire when that instance came from the container.
     *
     * @throws Exception
     */
    public function testRouteAttributesStillRunOnContainerBuiltInstance(): void
    {
        $server = (new Server())->withContainer($this->container([
            InjectedController::class => fn() => new InjectedController(new ContainerGreeter('injected')),
        ]));

        $this->assertEquals(
            '[{"x":"Before Process"},{"greeting":"injected"}]',
            $this->dispatch($server, '/attributes')
        );
    }
}
