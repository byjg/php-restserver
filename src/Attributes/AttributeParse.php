<?php

namespace ByJG\RestServer\Attributes;

use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use InvalidArgumentException;
use ReflectionAttribute;
use ReflectionException;
use ReflectionMethod;

class AttributeParse
{
    /**
     * @param class-string $attributeInstance
     * @throws ReflectionException
     */
    public static function processAttribute(string $attributeInstance, object $entity, string $method, HttpResponse $response, HttpRequest $request): void
    {
        $reflection = new ReflectionMethod($entity, $method);
        $attributes = $reflection->getAttributes($attributeInstance, ReflectionAttribute::IS_INSTANCEOF);

        foreach ($attributes as $attribute) {
            if ($attributeInstance === BeforeRouteInterface::class) {
                $attribute->newInstance()->processBefore($response, $request);
            } else if ($attributeInstance === AfterRouteInterface::class) {
                $attribute->newInstance()->processAfter($response, $request);
            } else {
                throw new InvalidArgumentException("Attribute not found");
            }
        }
    }

}