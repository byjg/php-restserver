<?php

namespace Tests;

use ByJG\RestServer\Exception\OperationIdInvalidException;
use ByJG\RestServer\Exception\SchemaInvalidException;
use ByJG\RestServer\Exception\SchemaNotFoundException;
use ByJG\RestServer\OutputProcessor\XmlOutputProcessor;
use ByJG\RestServer\Route\RoutePattern;
use ByJG\RestServer\Route\SwaggerRouteDefinition;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\InvalidArgumentException;

class ServerRequestHandlerSwaggerTest extends TestCase
{
    /**
     * @throws OperationIdInvalidException
     * @throws SchemaInvalidException
     * @throws SchemaNotFoundException
     * @throws InvalidArgumentException
     */
    public function testGenerateRoutesSwagger()
    {
        //$this->object->setPathOutputProcessor('get', '/v2/pet/{petId}', JsonCleanOutputProcessor::class);
        $object = new SwaggerRouteDefinition(__DIR__ . '/swagger-example.json');

        $this->assert($object);
    }

    /**
     * @throws OperationIdInvalidException
     * @throws SchemaInvalidException
     * @throws SchemaNotFoundException
     * @throws InvalidArgumentException
     */
    public function testGenerateRoutesOpenApi()
    {
        //$this->object->setPathOutputProcessor('get', '/v2/pet/{petId}', JsonCleanOutputProcessor::class);
        $object = new SwaggerRouteDefinition(__DIR__ . '/openapi-example.json', XmlOutputProcessor::class);

        $this->assert($object);
    }

    protected function assert(SwaggerRouteDefinition $object)
    {
        $this->assertEquals(
            [
                new RoutePattern(
                    "GET",
                    "/v2/pet/findByStatus",
                    "ByJG\RestServer\OutputProcessor\XmlOutputProcessor",
                    "PetStore\Pet",
                    "findPetsByStatus"
                ),
                new RoutePattern(
                    "GET",
                    "/v2/pet/findByTags",
                    "ByJG\RestServer\OutputProcessor\XmlOutputProcessor",
                    "PetStore\Pet",
                    "findPetsByTags"
                ),
                new RoutePattern(
                    "POST",
                    "/v2/pet",
                    "ByJG\RestServer\OutputProcessor\XmlOutputProcessor",
                    "PetStore\Pet",
                    "addPet"
                ),
                new RoutePattern(
                    "PUT",
                    "/v2/pet",
                    "ByJG\RestServer\OutputProcessor\JsonOutputProcessor",
                    "PetStore\Pet",
                    "updatePet"
                ),
                new RoutePattern(
                    "GET",
                    "/v2/store/inventory",
                    "ByJG\RestServer\OutputProcessor\JsonOutputProcessor",
                    "PetStore\Pet",
                    "getInventory"
                ),
                new RoutePattern(
                    "POST",
                    "/v2/store/order",
                    "ByJG\RestServer\OutputProcessor\XmlOutputProcessor",
                    "PetStore\Pet",
                    "placeOrder"
                ),
                new RoutePattern(
                    "POST",
                    "/v2/user/createWithArray",
                    "ByJG\RestServer\OutputProcessor\XmlOutputProcessor",
                    "PetStore\Pet",
                    "createUsersWithArrayInput"
                ),
                new RoutePattern(
                    "POST",
                    "/v2/user/createWithList",
                    "ByJG\RestServer\OutputProcessor\XmlOutputProcessor",
                    "PetStore\Pet",
                    "createUsersWithListInput"
                ),
                new RoutePattern(
                    "GET",
                    "/v2/user/login",
                    "ByJG\RestServer\OutputProcessor\XmlOutputProcessor",
                    "PetStore\Pet",
                    "loginUser"
                ),
                new RoutePattern(
                    "GET",
                    "/v2/user/logout",
                    "ByJG\RestServer\OutputProcessor\XmlOutputProcessor",
                    "PetStore\Pet",
                    "logoutUser"
                ),
                new RoutePattern(
                    "POST",
                    "/v2/user",
                    "ByJG\RestServer\OutputProcessor\XmlOutputProcessor",
                    "PetStore\Pet",
                    "createUser"
                ),
                new RoutePattern(
                    "POST",
                    "/v2/pet/{petId}/uploadImage",
                    "ByJG\RestServer\OutputProcessor\JsonOutputProcessor",
                    "PetStore\Pet",
                    "uploadFile"
                ),
                new RoutePattern(
                    "GET",
                    "/v2/pet/{petId}",
                    "ByJG\RestServer\OutputProcessor\JsonCleanOutputProcessor",
                    "PetStore\Pet",
                    "getPetById"
                ),
                new RoutePattern(
                    "POST",
                    "/v2/pet/{petId}",
                    "ByJG\RestServer\OutputProcessor\XmlOutputProcessor",
                    "PetStore\Pet",
                    "updatePetWithForm"
                ),
                new RoutePattern(
                    "DELETE",
                    "/v2/pet/{petId}",
                    "ByJG\RestServer\OutputProcessor\XmlOutputProcessor",
                    "PetStore\Pet",
                    "deletePet"
                ),
                new RoutePattern(
                    "GET",
                    "/v2/store/order/{orderId}",
                    "ByJG\RestServer\OutputProcessor\XmlOutputProcessor",
                    "PetStore\Pet",
                    "getOrderById"
                ),
                new RoutePattern(
                    "DELETE",
                    "/v2/store/order/{orderId}",
                    "ByJG\RestServer\OutputProcessor\XmlOutputProcessor",
                    "PetStore\Pet",
                    "deleteOrder"
                ),
                new RoutePattern(
                    "GET",
                    "/v2/user/{username}",
                    "ByJG\RestServer\OutputProcessor\XmlOutputProcessor",
                    "PetStore\Pet",
                    "getUserByName"
                ),
                new RoutePattern(
                    "PUT",
                    "/v2/user/{username}",
                    "ByJG\RestServer\OutputProcessor\XmlOutputProcessor",
                    "PetStore\Pet",
                    "updateUser"
                ),
                new RoutePattern(
                    "DELETE",
                    "/v2/user/{username}",
                    "ByJG\RestServer\OutputProcessor\XmlOutputProcessor",
                    "PetStore\Pet",
                    "deleteUser"
                ),
            ],
            $object->getRoutes()
        );
    }
}
