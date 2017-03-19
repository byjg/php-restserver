<?php

namespace ByJG\RestServer\HandleOutput;

use ByJG\RestServer\ServiceAbstract;
use ByJG\RestServer\Whoops\JsonResponseHandler;
use ByJG\Serializer\Formatter\JsonFormatter;

class JsonHandler implements HandleOutputInterface
{

    public function writeHeader()
    {
        header('Content-Type: application/json');
        // header('Access-Control-Allow-Origin: *');
        // header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
        // header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
    }

    public function execute(ServiceAbstract $instance)
    {
        $serialized = $instance->getResponse()->getResponseBag()->process();
        return (new JsonFormatter())->process($serialized);
    }

    public function getErrorHandler()
    {
        return new JsonResponseHandler();
    }
}
