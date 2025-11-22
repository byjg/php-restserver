<?php

namespace ByJG\RestServer\Middleware;

use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use Exception;

class MiddlewareManagement
{
    /**
     * Undocumented function
     *
     * @param mixed $middlewareList
     * @param mixed $dispatcherStatus
     * @param HttpResponse $response
     * @param HttpRequest $request
     * @return MiddlewareResult
     */
    public static function processBefore(
        array $middlewareList,
        mixed $dispatcherStatus,
        HttpResponse $response,
        HttpRequest $request
    ): MiddlewareResult
    {
        return self::processMiddleware($middlewareList, $dispatcherStatus, $response, $request);
    }


    /**
     * Process the middleware list
     *
     * @param mixed $middlewareList
     * @param HttpResponse $response
     * @param HttpRequest $request
     * @param string|null $class
     * @param string|null $method
     * @param Exception|null $exception
     * @return MiddlewareResult
     */
    public static function processAfter(
        array $middlewareList,
        HttpResponse $response,
        HttpRequest $request,
        ?string $class,
        ?string $method,
        ?Exception $exception
    ): MiddlewareResult
    {
        return self::processMiddleware($middlewareList, null, $response, $request, $class, $method, $exception);
    }

    /**
     * Process the middleware list
     *
     * @param mixed $middlewareList
     * @param mixed $dispatcherStatus
     * @param HttpResponse $response
     * @param HttpRequest $request
     * @param string|null $class
     * @param string|null $method
     * @param Exception|null $exception
     * @return MiddlewareResult
     */
    protected static function processMiddleware(
        array $middlewareList,
        mixed $dispatcherStatus,
        HttpResponse $response,
        HttpRequest $request,
        ?string $class = null,
        ?string $method = null,
        ?Exception $exception = null
    ): MiddlewareResult
    {
        $continue = MiddlewareResult::continue;

        if (empty($middlewareList)) {
            return $continue;
        }

        foreach ($middlewareList as $item) {
            $middleWare = $item['middleware'];
            $routePattern = $item['routePattern'];

            $requestPath = $request->getRequestPath();
            $requestPathStr = is_array($requestPath) ? '' : (string)$requestPath;
            if (!is_null($routePattern) && !preg_match("~$routePattern~", $requestPathStr)) {
                continue;
            }

            if (!is_null($dispatcherStatus)) {
                $result = $middleWare->beforeProcess($dispatcherStatus, $response, $request);
            } else {
                $result = $middleWare->afterProcess($response, $request, $class, $method, $exception);
            }
            
            if ($result === MiddlewareResult::stopProcessingOthers) {
                return $result;
            } elseif ($result === MiddlewareResult::stopProcessing) {
                $continue = $result;
            }
        }
        return $continue;
    }
}
