<?php

namespace Tests\OutputProcessor;

/**
 * A response model with a non-nullable typed property that is left uninitialized. Serializing it
 * used to raise "Typed property ... must not be accessed before initialization"; the property must
 * instead be omitted from the output.
 */
class UninitializedFieldModel
{
    public string $name;

    public int $missing;

    public function __construct(string $name)
    {
        $this->name = $name;
        // $this->missing is intentionally left uninitialized.
    }
}