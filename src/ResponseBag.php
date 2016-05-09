<?php

namespace ByJG\RestServer;

use ByJG\AnyDataset\Model\ObjectHandler;
use ByJG\RestServer\Exception\HttpResponseException;
use ByJG\Util\XmlUtil;
use DOMNode;

class ResponseBag
{

    protected $collection;

    public function add($object)
    {
        if (!is_object($object) && !is_array($object)) {
            throw new HttpResponseException('You can add only object');
        }
        $this->collection[] = $object;
    }

    /**
     *
     * @param DOMNode $current
     * @param string $annotationPrefix
     * @return \DOMDocument XML Node
     * @throws \ByJG\Util\Exception\XmlUtilException
     */
    public function process(DOMNode $current = null, $annotationPrefix = 'object')
    {
        $xmlDoc = null;
        if (is_null($current)) {
            $xmlDoc = XmlUtil::createXmlDocument();
            $current = XmlUtil::createChild($xmlDoc, "root");
        }

        foreach ((array)$this->collection as $object) {
            if ($object instanceof ResponseBag) {
                $object->process($current);
            } else {
                $objHandler = new ObjectHandler($current, $object, $annotationPrefix);
                $objHandler->createObjectFromModel();
            }
        }

        return $xmlDoc;
    }
}
