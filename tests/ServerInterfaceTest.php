<?php

namespace Tests;

use ByJG\RestServer\MockServer;
use ByJG\RestServer\Server;
use ByJG\RestServer\ServerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ServerInterfaceTest extends TestCase
{
    public static function serverProvider(): array
    {
        return [
            [Server::class],
            [MockServer::class],
        ];
    }

    #[DataProvider('serverProvider')]
    public function testHandleDefaultsMatchInterface(string $class): void
    {
        $interface = new ReflectionMethod(ServerInterface::class, 'handle');
        $implementation = new ReflectionMethod($class, 'handle');

        foreach ($interface->getParameters() as $index => $parameter) {
            if (!$parameter->isDefaultValueAvailable()) {
                continue;
            }
            $this->assertSame(
                $parameter->getDefaultValue(),
                $implementation->getParameters()[$index]->getDefaultValue(),
                "Default of \${$parameter->getName()} differs between ServerInterface and $class"
            );
        }
    }
}
