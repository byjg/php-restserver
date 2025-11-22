<?php

namespace ByJG\RestServer\Middleware;

use ByJG\RestServer\Exception\Error401Exception;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\SerializationRuleEnum;
use Override;

class CorsMiddleware implements BeforeMiddlewareInterface
{

    const CORS_OK = 'CORS_OK';
    const CORS_FAILED = 'CORS_FAILED';
    const CORS_OPTIONS = 'CORS_OPTIONS';

    protected array $corsOrigins = ['.*'];
    protected array $corsMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];
    protected array $corsHeaders = [
        'Authorization',
        'Content-Type',
        'Accept',
        'Origin',
        'User-Agent',
        'Cache-Control',
        'Keep-Alive',
        'X-Requested-With',
        'If-Modified-Since'
    ];

    /**
     * Pre-flight CORS verification
     *
     * @param mixed $dispatcherStatus
     * @param HttpResponse $response
     * @param HttpRequest $request
     * @return MiddlewareResult
     * @throws Error401Exception
     */
    #[Override]
    public function beforeProcess(
        mixed        $dispatcherStatus,
        HttpResponse $response,
        HttpRequest  $request
    ): MiddlewareResult
    {
        $corsStatus = $this->preFlight($response, $request);
        if ($corsStatus != self::CORS_OK) {
            if ($corsStatus == self::CORS_OPTIONS) {
                $response->emptyResponse();
                $response->getResponseBag()->setSerializationRule(SerializationRuleEnum::Raw);
                return MiddlewareResult::stopProcessingOthers;
            } elseif ($corsStatus == self::CORS_FAILED) {
                throw new Error401Exception("CORS verification failed. Request Blocked.");
            }
        }

        return MiddlewareResult::continue;
    }

    /**
     * Pre-flight CORS verification
     *
     * @param HttpResponse $response
     * @param HttpRequest $request
     *
     * @return string
     */
    protected function preFlight(HttpResponse $response, HttpRequest $request): string
    {
        // TODO: Still missing some headers
        // https://developer.mozilla.org/en-US/docs/Glossary/Preflight_request
        $corsStatus = self::CORS_OK;

        if (!empty($request->server('HTTP_ORIGIN'))) {
            $corsStatus = self::CORS_FAILED;

            foreach ($this->corsOrigins as $origin) {
                $httpOrigin = $request->server('HTTP_ORIGIN');
                $httpOriginStr = is_array($httpOrigin) ? '' : (string)$httpOrigin;
                if (preg_match("~^.*//$origin$~", $httpOriginStr)) {
                    $response->addHeader("Access-Control-Allow-Origin", $httpOriginStr);
                    $response->addHeader('Access-Control-Allow-Credentials', 'true');
                    $response->addHeader('Access-Control-Max-Age', '86400');    // cache for 1 day

                    // Access-Control headers are received during OPTIONS requests
                    if ($request->server('REQUEST_METHOD') == 'OPTIONS') {
                        $response->setResponseCode(204, 'No Content');
                        $response->addHeader("Access-Control-Allow-Methods", implode(",", array_merge(['OPTIONS'], $this->corsMethods)));
                        $response->addHeader("Access-Control-Allow-Headers", implode(",", $this->corsHeaders));
                        return self::CORS_OPTIONS;
                    }
                    $corsStatus = self::CORS_OK;
                    break;
                }
            }
        }
        return $corsStatus;
    }

    public function withCorsOrigins(array|string $origins): static
    {
        $this->corsOrigins = (array)$origins;
        return $this;
    }

    public function withAcceptCorsHeaders(array $headers): static
    {
        $this->corsHeaders = $headers;
        return $this;
    }

    public function withAcceptCorsMethods(array $methods): static
    {
        $this->corsMethods = $methods;
        return $this;
    }
}
