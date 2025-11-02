<?php

namespace ByJG\RestServer\Attributes;

use Attribute;
use ByJG\RestServer\Exception\Error401Exception;
use ByJG\RestServer\Exception\Error403Exception;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\Middleware\JwtMiddleware;
use Override;

#[Attribute(Attribute::TARGET_METHOD)]
class RequireRole implements BeforeRouteInterface
{
    protected string $role;
    protected string $roleParam;
    protected ?string $roleKey;

    /**
     * @param string $role The required role value
     * @param string $roleParam The parameter path where the role is stored (default: 'role')
     * @param string|null $roleKey Optional key to extract from the parameter if it's an array (e.g., 'role' to get $data['role'])
     */
    public function __construct(string $role, string $roleParam = 'role', ?string $roleKey = null)
    {
        $this->role = $role;
        $this->roleParam = $roleParam;
        $this->roleKey = $roleKey;
    }

    /**
     * @throws Error401Exception
     * @throws Error403Exception
     */
    #[Override]
    public function processBefore(HttpResponse $response, HttpRequest $request): void
    {
        // First check if authenticated
        if ($request->param(JwtMiddleware::JWT_PARAM_PARSE_STATUS) !== JwtMiddleware::JWT_SUCCESS) {
            $message = $request->param(JwtMiddleware::JWT_PARAM_PARSE_MESSAGE) ?? 'Authentication required';
            throw new Error401Exception($message);
        }

        // Then check the role
        $userRole = $request->param($this->roleParam);

        // If roleKey is specified and data is an array, extract the value from the array
        if (!empty($userRole) && $this->roleKey !== null) {
            if (is_array($userRole)) {
                $userRole = $userRole[$this->roleKey] ?? null;
            } elseif (is_object($userRole)) {
                $userRole = $userRole->{$this->roleKey} ?? null;
            }
        }

        if (empty($userRole) || $userRole !== $this->role) {
            throw new Error403Exception('Insufficient privileges');
        }
    }
}
