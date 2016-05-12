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
        $object = new SerializerObject((array)$this->collection);
        return $object->build();
    }

    public function getCollection()
    {
        return $this->collection;
    }
}
