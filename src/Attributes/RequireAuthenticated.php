<?php

namespace ByJG\RestServer\Attributes;

use Attribute;
use ByJG\RestServer\Exception\Error401Exception;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\Middleware\JwtMiddleware;

#[Attribute(Attribute::TARGET_METHOD)]
class RequireAuthenticated implements BeforeRouteInterface
{
    /**
     * @throws Error401Exception
     */
    public function processBefore(HttpResponse $response, HttpRequest $request): void
    {
        if ($request->param(JwtMiddleware::JWT_PARAM_PARSE_STATUS) !== JwtMiddleware::JWT_SUCCESS) {
            throw new Error401Exception($request->param(JwtMiddleware::JWT_PARAM_PARSE_MESSAGE));
        }
    }
}
