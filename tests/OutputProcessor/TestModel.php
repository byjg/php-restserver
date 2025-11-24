<?php

namespace Tests\OutputProcessor;

class TestModel
{
    public string $name;
    public string $address1;
    public ?string $address2;
    public int $value;

    public function __construct(string $name, string $address1, ?string $address2, int $value)
    {
        $this->name = $name;
        $this->address1 = $address1;
        $this->address2 = $address2;
        $this->value = $value;
    }
}
