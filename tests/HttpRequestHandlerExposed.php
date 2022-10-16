<?php


namespace Tests;


use ByJG\RestServer\HttpRequestHandler;

class HttpRequestHandlerExposed extends HttpRequestHandler
{
    // Exposing protected method
    public function mimeContentType($filename)
    {
        return parent::mimeContentType($filename);
    }

    // Exposing protected method
    public function tryDeliveryPhysicalFile()
    {
        return parent::tryDeliveryPhysicalFile();
    }
}