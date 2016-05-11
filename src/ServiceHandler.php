<?php

namespace ByJG\RestServer;

use ByJG\Serialize\Formatter\JsonFormatter;
use ByJG\Serialize\Formatter\XmlFormatter;

class ServiceHandler implements HandlerInterface
{

    protected $output = Output::JSON;

    public function getOutput()
    {
        return $this->output;
    }

    public function setOutput($output)
    {
        // Check if output is set
        if ($output != Output::JSON && $output != Output::XML) {
            throw new \Exception('Invalid output format. Valid are XML or JSON');
        }

        $this->output = $output;
    }

    public function setHeader()
    {
        switch ($this->getOutput()) {
            case Output::JSON:
                header('Content-Type: application/json');
                break;

            case Output::XML:
                header('Content-Type: text/xml');
                break;
        }
    }

    /**
     * CORS - Better do that in your http server, but you can enable calling this
     */
    public function setHeaderCors()
    {
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method == "OPTIONS") {
            return false;
        }
        return true;
    }

    public function execute(ServiceAbstract $instance)
    {

        $serialized = $instance->getResponse()->getResponseBag()->process();

        switch ($this->getOutput()) {
            case Output::JSON:
                return (new JsonFormatter())->process($serialized);

            case Output::XML:
                return (new XmlFormatter())->process($serialized);

        }

        return null;
    }
}
