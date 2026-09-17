<?php

namespace Tests\Routes;

use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;

/**
 * A controller that declares its dependency in the constructor. It cannot be built with
 * `new $className()`, so reaching this controller at all proves the container was used.
 */
class InjectedController
{
    public function __construct(protected ContainerGreeter $greeter)
    {
    }

    public function index(HttpResponse $response, HttpRequest $request): void
    {
        $response->write(["greeting" => $this->greeter->greet()]);
    }

    #[BeforeProcess]
    public function withAttributes(HttpResponse $response, HttpRequest $request): void
    {
        $response->write(["greeting" => $this->greeter->greet()]);
    }
}
