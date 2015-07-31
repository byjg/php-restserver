<?php

namespace ByJG\RestServer;

use BadMethodCallException;
use ByJG\AnyDataset\Model\ObjectHandler;
use ByJG\RestServer\Exception\ClassNotFoundException;
use ByJG\RestServer\Exception\InvalidClassException;
use ByJG\Util\XmlUtil;

class ServiceHandler
{
    protected $output = Output::JSON;

    public function getOutput()
    {
        return $this->output;
    }

    public function setOutput($output)
    {
        $this->output = $output;
    }

    public function __construct($output)
    {
        $this->setOutput($output);
    }

    public function execute($class)
    {
        if (!class_exists($class))
        {
            throw new ClassNotFoundException("Class $class not found");
        }

        $instance = new $class();

        if (!($instance instanceof ServiceAbstract))
        {
            throw new InvalidClassException("Class $class is not instance of ServiceAbstract");
        }

		$method = strtolower($instance->getRequest()->server("REQUEST_METHOD"));

		$customAction = $method . ($instance->getRequest()->get('action'));

		if (method_exists($instance, $customAction)) {
            $instance->$customAction();
        } else {
            throw new BadMethodCallException("The method '$customAction' does not exists.");
        }

        $dom = $instance->getResponse()->getResponseBag()->process();

        switch ($this->getOutput())
        {
            case Output::JSON:
                return ObjectHandler::xml2json($dom);

            case Output::XML:
                return $dom->saveXML();

            case Output::CSV:
                $array = XmlUtil::xml2Array($dom);

                $return = "";
                foreach ((array)$array as $line)
                {
                    foreach((array)$line as $field)
                    {
                        $return .= "\"" . str_replace('"', '\\"', (is_array($field) ? json_encode($field) : $field)) . "\";";
                    }
                    $return .= "\n";
                }
                return $return;

        }
    }
}
