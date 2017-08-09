<?php

namespace ByJG\RestServer\HandleOutput;

use ByJG\RestServer\ServiceAbstract;
use ByJG\Serializer\Formatter\PlainTextFormatter;
use Whoops\Handler\PrettyPageHandler;

class HtmlHandler implements HandleOutputInterface
{

    public function writeHeader()
    {
        header('Content-Type: text/html');
    }

    public function writeOutput(ServiceAbstract $instance)
    {
        $serialized = $instance->getResponse()->getResponseBag()->process();
        return (new PlainTextFormatter())->process($serialized);
    }

    public function getErrorHandler()
    {
        return new PrettyPageHandler();
    }
}
