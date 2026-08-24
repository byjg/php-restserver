<?php

namespace Tests\Routes;

use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;

/**
 * A controller with no constructor — the shape every existing application uses, and the
 * one that must keep working when no container is set.
 */
class PlainController
{
    public function index(HttpResponse $response, HttpRequest $request): void
    {
        $response->write(["source" => "new"]);
    }
}
