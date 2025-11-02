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
    protected string $roleParam;

    /**
     * @param string $role The required role value
     * @param string $roleParam The parameter path where the role is stored (default: 'role')
     */
    public function __construct(string $role, string $roleParam = 'role')
    {
        $this->role = $role;
        $this->roleParam = $roleParam;
    }

    /**
     * @throws Error401Exception
     * @throws Error403Exception
     */
    public function processBefore(HttpResponse $response, HttpRequest $request): void
    {
        // First check if authenticated
        if ($request->param(JwtMiddleware::JWT_PARAM_PARSE_STATUS) !== JwtMiddleware::JWT_SUCCESS) {
            $message = $request->param(JwtMiddleware::JWT_PARAM_PARSE_MESSAGE) ?? 'Authentication required';
            throw new Error401Exception($message);
        }

        // Then check the role
        $userRole = $request->param($this->roleParam);

        if ($userRole !== $this->role) {
            throw new Error403Exception('Insufficient privileges');
        }
    }
}
