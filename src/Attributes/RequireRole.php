<?php

namespace ByJG\RestServer\Attributes;

use Attribute;
use ByJG\RestServer\Exception\Error401Exception;
use ByJG\RestServer\Exception\Error403Exception;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\Middleware\JwtMiddleware;

#[Attribute(Attribute::TARGET_METHOD)]
class RequireRole implements BeforeRouteInterface
{
    protected string $role;

    public function __construct(string $role)
    {
        $this->role = $role;
    }

    /**
     * @throws Error401Exception
     * @throws Error403Exception
     */
    public function processBefore(HttpResponse $response, HttpRequest $request): void
    {
        // First check if authenticated
        if ($request->param(JwtMiddleware::JWT_PARAM_PARSE_STATUS) !== JwtMiddleware::JWT_SUCCESS) {
            throw new Error401Exception($request->param(JwtMiddleware::JWT_PARAM_PARSE_MESSAGE));
        }

        // Then check the role
        $data = (array)$request->param("jwt.data");
        $userRole = $data['role'] ?? null;

        if ($userRole !== $this->role) {
            throw new Error403Exception('Insufficient privileges');
        }
    }
}
