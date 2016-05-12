<?php

namespace ByJG\RestServer;

use ByJG\RestServer\Exception\HttpResponseException;
use ByJG\Serializer\SerializerObject;

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
     * @return array
     */
    public function process()
    {
        $collection = (array)$this->collection;
        if (count($collection)===1) {
            $collection = $collection[0];
        }
        
        $object = new SerializerObject($collection);
        return $object->build();
    }

    public function getCollection()
    {
        return $this->collection;
    }
}
