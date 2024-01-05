<?php

namespace ByJG\RestServer\Middleware;

use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;

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
        $middlewareList,
        $dispatcherStatus,
        HttpResponse $response,
        HttpRequest $request
    ) {
        return self::processMiddleware($middlewareList, $dispatcherStatus, $response, $request);
    }


    /**
     * Process the middleware list
     *
     * @param mixed $middlewareList
     * @param HttpResponse $response
     * @param HttpRequest $request
     * @param $class
     * @param $method
     * @param $exception
     * @return MiddlewareResult
     */
    public static function processAfter(
        $middlewareList,
        HttpResponse $response,
        HttpRequest $request,
        $class,
        $method,
        $exception
    ) {
        return self::processMiddleware($middlewareList, null, $response, $request, $class, $method, $exception);
    }

    /**
     * Process the middleware list
     *
     * @param mixed $middlewareList
     * @param mixed $dispatcherStatus
     * @param HttpResponse $response
     * @param HttpRequest $request
     * @param null $class
     * @param null $method
     * @param null $exception
     * @return MiddlewareResult
     */
    protected static function processMiddleware(
        $middlewareList,
        $dispatcherStatus,
        HttpResponse $response,
        HttpRequest $request,
        $class = null,
        $method = null,
        $exception = null
    ) {
        $continue = MiddlewareResult::continue();

        if (empty($middlewareList)) {
            return $continue;
        }

        foreach ($middlewareList as $middleWare) {
            if (!is_null($dispatcherStatus)) {
                $result = $middleWare->beforeProcess($dispatcherStatus, $response, $request);
            } else {
                $result = $middleWare->afterProcess($response, $request, $class, $method, $exception);
            }
            
            if ($result->getStatus() === MiddlewareResult::STOP_PROCESSING_OTHERS) {
                return $result;
            } elseif ($result->getStatus() === MiddlewareResult::STOP_PROCESSING) {
                $continue = $result;
            }
        }
        return $continue;
    }
}
