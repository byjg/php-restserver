<?php

namespace ByJG\RestServer;

use ByJG\Serializer\Serialize;
use InvalidArgumentException;

class ResponseBag
{

    const AUTOMATIC = 0;
    const SINGLE_OBJECT = 1;
    const OBJECT_LIST = 2;
    const RAW = 3;

    protected $collection = [];
    protected $serializationRule = ResponseBag::AUTOMATIC;

    /**
     * @param string|mixed $object
     */
    public function add($object)
    {
        if (!is_string($object) && !is_numeric($object) && $this->serializationRule === ResponseBag::RAW) {
            throw new InvalidArgumentException("Raw data can be only string or numbers");
        }

        if (!is_object($object) && !is_array($object)) {
            $object = [ $object ];
        }

        if ($this->serializationRule !== ResponseBag::SINGLE_OBJECT && $this->serializationRule !== ResponseBag::RAW) {
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
     * @return array|string
     */
    public function process($buildNull = true, $onlyString = false)
    {
        $collection = (array)$this->collection;
        if ($this->serializationRule === ResponseBag::RAW) {
            return implode("", $collection);
        }

        if (count($collection) === 1
            && $this->serializationRule !== ResponseBag::OBJECT_LIST && isset($collection[0])
        ) {
            $collection = $collection[0];
        }

        if (!is_object($collection) && !is_array($collection)) {
            return "$collection";
        }
        
        $object = Serialize::from($collection)->withOnlyString($onlyString);

        if (!$buildNull) {
            $object->withDoNotParseNullValues();
        }
        return $object->toArray();
    }

    public function getCollection()
    {
        return $this->collection;
    }

    public function setSerializationRule($value)
    {
        $this->serializationRule = $value;
    }

    public function getSerializationRule()
    {
        return $this->serializationRule;
    }
}
