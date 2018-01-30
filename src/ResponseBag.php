<?php

namespace ByJG\RestServer;

use ByJG\Serializer\SerializerObject;

class ResponseBag
{

    const AUTOMATIC = 0;
    const SINGLE_OBJECT = 1;
    const OBJECT_LIST = 2;

    protected $collection = [];
    protected $serializationRule = ResponseBag::AUTOMATIC;

    /**
     * @param string|mixed $object
     */
    public function add($object)
    {
        if (!is_object($object) && !is_array($object)) {
            $object = [ $object ];
        }

        if ($this->serializationRule !== ResponseBag::SINGLE_OBJECT) {
            $this->collection[] = $object;
            return;
        }

        if (is_object($object)) {
            $object = [$object];
        }
        $this->collection = array_merge($this->collection, $object);
    }

    /**
     * @param bool $buildNull
     * @param bool $onlyString
     * @return array
     */
    public function process($buildNull = true, $onlyString = false)
    {
        $collection = (array)$this->collection;
        if (count($collection) === 1
            && $this->serializationRule !== ResponseBag::OBJECT_LIST && isset($collection[0])
        ) {
            $collection = $collection[0];
        }
        
        $object = new SerializerObject($collection);
        return $object
            ->setOnlyString($onlyString)
            ->setBuildNull($buildNull)
            ->build();
    }

    public function getCollection()
    {
        return $this->collection;
    }

    public function serializationRule($value)
    {
        $this->serializationRule = $value;
    }
}
