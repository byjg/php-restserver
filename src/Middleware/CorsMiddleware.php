<?php

namespace ByJG\RestServer\Middleware;

use ByJG\RestServer\Exception\Error401Exception;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\ResponseBag;

class CorsMiddleware implements BeforeMiddlewareInterface
{

    const CORS_OK = 'CORS_OK';
    const CORS_FAILED = 'CORS_FAILED';
    const CORS_OPTIONS = 'CORS_OPTIONS';

    protected $corsOrigins = ['.*'];
    protected $corsMethods = [ 'GET', 'POST', 'PUT', 'DELETE', 'PATCH'];
    protected $corsHeaders = [
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
     * Undocumented function
     *
     * @param mixed $dispatcherStatus
     * @param HttpResponse $response
     * @param HttpRequest $request
     * @return MiddlewareResult
     */
    public function beforeProcess(
        $dispatcherStatus,
        HttpResponse $response,
        HttpRequest $request
    )
    {
        $corsStatus = $this->validateCors($response, $request);
        if ($corsStatus != self::CORS_OK) {
            if ($corsStatus == self::CORS_OPTIONS) {
                $response->emptyResponse();
                $response->getResponseBag()->setSerializationRule(ResponseBag::RAW);
                return MiddlewareResult::stopProcessingOthers();
            } elseif ($corsStatus == self::CORS_FAILED) {
                throw new Error401Exception("CORS verification failed. Request Blocked.");
            }
        }

        return MiddlewareResult::continue();
    }

    /**
     * Undocumented function
     *
     * @param HttpResponse $response
     * @param HttpRequest $request
     * @return string
     */
    protected function validateCors(HttpResponse $response, HttpRequest $request)
    {
        $corsStatus = self::CORS_OK;

        if (!empty($request->server('HTTP_ORIGIN'))) {
            $corsStatus = self::CORS_FAILED;

            foreach ((array)$this->corsOrigins as $origin) {
                if (preg_match("~^.*//$origin$~", $request->server('HTTP_ORIGIN'))) {
                    $response->addHeader("Access-Control-Allow-Origin", $request->server('HTTP_ORIGIN'));
                    $response->addHeader('Access-Control-Allow-Credentials', 'true');
                    $response->addHeader('Access-Control-Max-Age', '86400');    // cache for 1 day

                    // Access-Control headers are received during OPTIONS requests
                    if ($request->server('REQUEST_METHOD') == 'OPTIONS') {
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

    public function withCorsOrigins($origins)
    {
        $this->corsOrigins = $origins;
        return $this;
    }

    public function withAcceptCorsHeaders($headers)
    {
        $this->corsHeaders = $headers;
        return $this;
    }

    public function withAcceptCorsMethods($methods)
    {
        $this->corsMethods = $methods;
        return $this;
    }
}
