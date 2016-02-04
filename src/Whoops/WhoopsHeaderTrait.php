<?php

namespace ByJG\RestServer\Whoops;

trait WhoopsHeaderTrait
{
    public function setProperHeader(\Exception $exception)
    {
        if ($exception instanceof \ByJG\RestServer\Exception\Error401Exception) {
            header('HTTP/1.0 401 Unathorized', true, 401);
        } elseif ($exception instanceof \ByJG\RestServer\Exception\Error402Exception) {
            header('HTTP/1.0 402 Payment Required', true, 402);
        } elseif ($exception instanceof \ByJG\RestServer\Exception\Error403Exception) {
            header('HTTP/1.0 403 Forbidden', true, 403);
        } elseif ($exception instanceof \ByJG\RestServer\Exception\Error404Exception) {
            header('HTTP/1.0 404 Not Found', true, 404);
        } elseif ($exception instanceof \ByJG\RestServer\Exception\ClassNotFoundException) {
            header('HTTP/1.0 404 Not Found', true, 404);
        } elseif ($exception instanceof \ByJG\RestServer\Exception\Error405Exception) {
            header('HTTP/1.0 405 Method not allowed', true, 405);
        } elseif ($exception instanceof \ByJG\RestServer\Exception\Error501Exception) {
            header('HTTP/1.0 501 Not Implemented', true, 501);
        } elseif ($exception instanceof \BadMethodCallException) {
            header('HTTP/1.0 501 Not Implemented', true, 501);
        } elseif ($exception instanceof \ByJG\RestServer\Exception\Error520Exception) {
            header('HTTP/1.0 520 Unknow Error', true, 501);
        }
    }
}
