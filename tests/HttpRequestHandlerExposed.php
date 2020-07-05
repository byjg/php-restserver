<?php


namespace Tests;


use ByJG\RestServer\HttpRequestHandler;

class HttpRequestHandlerExposed extends HttpRequestHandler
{
    public function mimeContentType($filename)
    {
        return parent::mimeContentType($filename);
    }

    public function tryDeliveryPhysicalFile()
    {
        return parent::tryDeliveryPhysicalFile();
    }
}