<?php

namespace ByJG\RestServer\HandleOutput;

use ByJG\RestServer\ServiceAbstract;
use ByJG\Serializer\Formatter\XmlFormatter;
use Whoops\Handler\XmlResponseHandler;

class XmlHandler implements HandleOutputInterface
{
    public function writeHeader()
    {
        header('Content-Type: text/xml');
    }

    public function writeOutput(ServiceAbstract $instance)
    {

        $serialized = $instance->getResponse()->getResponseBag()->process();
        return (new XmlFormatter())->process($serialized);
    }

    public function getErrorHandler()
    {
        return new XmlResponseHandler();
    }
}
