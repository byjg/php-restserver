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
        if (!is_object($object) && !is_array($object))
        {
            throw new HttpResponseException('You can add only object');
        }
        $this->collection[] = $object;
    }

    /**
     *
     * @param DOMNode $current
     * @return \DOMDocument XML Node
     */
    public function process(DOMNode $current = null)
    {
        $xmlDoc = null;
        if (is_null($current))
        {
            $xmlDoc = XmlUtil::CreateXmlDocument();
            $current = XmlUtil::CreateChild($xmlDoc, "root" );
        }

        foreach ((array)$this->collection as $object)
        {
            if ($object instanceof ResponseBag) {
                $object->process($current);
            }
            else {
                $objHandler = new ObjectHandler($current, $object, "object");
                $objHandler->CreateObjectFromModel();
            }
        }

        return $xmlDoc;
    }
}
