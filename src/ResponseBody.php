<?php

namespace ByJG\RestServer;

use ByJG\RestServer\Enum\OutputMode;
use ByJG\Serializer\Serialize;
use InvalidArgumentException;

class ResponseBody
{
    protected array $collection = [];
    protected OutputMode $outputMode = OutputMode::Automatic;

    /**
     * @param string|mixed $object
     */
    public function add(mixed $object): void
    {
        if (!is_string($object) && !is_numeric($object) && $this->outputMode === OutputMode::Plain) {
            throw new InvalidArgumentException("Plain output mode only accepts strings or numbers");
        }

        if (!is_object($object) && !is_array($object)) {
            $object = [ $object ];
        }

        if ($this->outputMode !== OutputMode::SingleObject && $this->outputMode !== OutputMode::Plain) {
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
        if ($this->outputMode === OutputMode::Plain) {
            return implode("", $collection);
        }

        if (count($collection) === 1
            && $this->outputMode !== OutputMode::ObjectList && isset($collection[0])
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

    public function serializeAs(OutputMode $value): void
    {
        $this->outputMode = $value;
    }

    public function getOutputMode(): OutputMode
    {
        return $this->outputMode;
    }
}
