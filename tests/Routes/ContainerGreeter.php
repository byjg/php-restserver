<?php

namespace Tests\Routes;

/**
 * A collaborator that a controller can only obtain through constructor injection.
 */
class ContainerGreeter
{
    public function __construct(protected string $greeting = 'default')
    {
    }

    public function greet(): string
    {
        return $this->greeting;
    }
}
