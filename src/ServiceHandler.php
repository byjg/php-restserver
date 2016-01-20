<?php

namespace ByJG\RestServer;

use ByJG\AnyDataset\Model\ObjectHandler;
use ByJG\Util\XmlUtil;

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
        if ($output != Output::JSON && $output != Output::XML && $output != Output::CSV && $output != Output::RDF) {
            throw new \Exception('Invalid output format. Valid are XML, JSON or CSV');
        }

        $this->output = $output;
    }

    public function setHeader()
    {
        switch ($this->getOutput()) {
            case Output::JSON:
                header('Content-Type: application/json');
                break;

            case Output::RDF:
                header('Content-Type: application/rdf+xml');
                break;

            case Output::XML:
                header('Content-Type: text/xml');
                break;

            default:
                header('Content-Type: text/plain');
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
        $root = null;
        $annotationPrefix = 'object';
        if ($this->getOutput() == Output::RDF) {
            $xmlDoc = XmlUtil::CreateXmlDocument();
            $root = XmlUtil::CreateChild($xmlDoc, "rdf:RDF", "", "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
            XmlUtil::AddNamespaceToDocument($root, "rdfs", "http://www.w3.org/2000/01/rdf-schema#");
            $annotationPrefix = 'rdf';
        }

        $dom = $instance->getResponse()->getResponseBag()->process($root, $annotationPrefix);

        switch ($this->getOutput()) {
            case Output::JSON:
                return ObjectHandler::xml2json($dom);

            case Output::XML:
            case Output::RDF:
                return $dom->saveXML();

            case Output::CSV:
                $array = XmlUtil::xml2Array($dom);

                $return = "";
                foreach ((array) $array as $line) {
                    foreach ((array) $line as $field) {
                        $return .= "\"" . str_replace('"', '\\"', (is_array($field) ? json_encode($field) : $field)) . "\";";
                    }
                    $return .= "\n";
                }
                return $return;
        }
    }
}
