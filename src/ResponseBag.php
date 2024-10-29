<?php

namespace ByJG\RestServer;

use ByJG\Serializer\Serialize;
use InvalidArgumentException;

class ResponseBag
{
    protected array $collection = [];
    protected SerializationRuleEnum $serializationRule = SerializationRuleEnum::Automatic;

    /**
     * @param string|mixed $object
     */
    public function add(mixed $object): void
    {
        if (!is_string($object) && !is_numeric($object) && $this->serializationRule === SerializationRuleEnum::Raw) {
            throw new InvalidArgumentException("Raw data can be only string or numbers");
        }

        if (!is_object($object) && !is_array($object)) {
            $object = [ $object ];
        }

        if ($this->serializationRule !== SerializationRuleEnum::SingleObject && $this->serializationRule !== SerializationRuleEnum::Raw) {
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
    public function process(bool $buildNull = true, bool $onlyString = false): array|string
    {
        $collection = $this->collection;
        if ($this->serializationRule === SerializationRuleEnum::Raw) {
            return implode("", $collection);
        }

        if (count($collection) === 1
            && $this->serializationRule !== SerializationRuleEnum::ObjectList && isset($collection[0])
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

    public function getCollection(): array
    {
        return $this->collection;
    }

    public function setSerializationRule(SerializationRuleEnum $value): void
    {
        $this->serializationRule = $value;
    }

    public function getSerializationRule(): SerializationRuleEnum
    {
        return $this->serializationRule;
    }
}
