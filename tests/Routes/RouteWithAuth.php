<?php

namespace Tests\Routes;

use ByJG\RestServer\Attributes\RequireAuthenticated;
use ByJG\RestServer\Attributes\RequireRole;
use ByJG\RestServer\Attributes\RouteDefinition;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;

class RouteWithAuth
{
    #[RouteDefinition('GET', '/public')]
    public function publicRoute(HttpResponse $response, HttpRequest $request): void
    {
        $response->write(['message' => 'Public access']);
    }

    #[RouteDefinition('GET', '/authenticated')]
    #[RequireAuthenticated]
    public function authenticatedRoute(HttpResponse $response, HttpRequest $request): void
    {
        $response->write(['message' => 'Authenticated access']);
    }

    #[RouteDefinition('GET', '/admin')]
    #[RequireRole('admin')]
    public function adminRoute(HttpResponse $response, HttpRequest $request): void
    {
        $response->write(['message' => 'Admin access']);
    }

    #[RouteDefinition('GET', '/user')]
    #[RequireRole('user')]
    public function userRoute(HttpResponse $response, HttpRequest $request): void
    {
        $response->write(['message' => 'User access']);
    }

    #[RouteDefinition('POST', '/admin/action')]
    #[RequireRole('admin')]
    public function adminAction(HttpResponse $response, HttpRequest $request): void
    {
        $response->write(['message' => 'Admin action executed']);
    }

    #[RouteDefinition('GET', '/array-role')]
    #[RequireRole('admin', 'jwt.data', 'role')]
    public function arrayRoleRoute(HttpResponse $response, HttpRequest $request): void
    {
        $response->write(['message' => 'Admin access granted']);
    }
}
