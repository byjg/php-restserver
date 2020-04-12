<?php

namespace Tests;

use ByJG\RestServer\Exception\OperationIdInvalidException;
use ByJG\RestServer\Exception\SchemaInvalidException;
use ByJG\RestServer\Exception\SchemaNotFoundException;
use ByJG\RestServer\OutputProcessor\JsonCleanOutputProcessor;
use ByJG\RestServer\OutputProcessor\XmlOutputProcessor;
use ByJG\RestServer\RoutePattern;
use ByJG\RestServer\ServerRequestHandler;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\InvalidArgumentException;

class ServerRequestHandlerSwaggerTest extends TestCase
{
    /**
     * @var ServerRequestHandler
     */
    protected $object;

    public function setUp()
    {
        $this->object = new ServerRequestHandler();
    }

    public function tearDown()
    {
        $this->object = null;
    }

    /**
     * @throws OperationIdInvalidException
     * @throws SchemaInvalidException
     * @throws SchemaNotFoundException
     * @throws InvalidArgumentException
     */
    public function testGenerateRoutesSwagger()
    {
        $this->object->setPathHandler('get', '/v2/pet/{petId}', JsonCleanOutputProcessor::class);
        $this->object->setRoutesSwagger(__DIR__ . '/swagger-example.json');

        $this->assert();
    }

    /**
     * @throws OperationIdInvalidException
     * @throws SchemaInvalidException
     * @throws SchemaNotFoundException
     * @throws InvalidArgumentException
     */
    public function testGenerateRoutesOpenApi()
    {
        $this->object->setPathHandler('get', '/v2/pet/{petId}', JsonCleanOutputProcessor::class);
        $this->object->setDefaultHandler(new XmlOutputProcessor());
        $this->object->setRoutesSwagger(__DIR__ . '/openapi-example.json');

        $this->assert();
    }

    protected function assert()
    {
        $this->assertEquals(
            [
                new RoutePattern(
                    "GET",
                    "/v2/pet/findByStatus",
                    "ByJG\RestServer\OutputProcessor\XmlOutputProcessor",
                    "findPetsByStatus",
                    "PetStore\Pet"
                ),
                new RoutePattern(
                    "GET",
                    "/v2/pet/findByTags",
                    "ByJG\RestServer\OutputProcessor\XmlOutputProcessor",
                    "findPetsByTags",
                    "PetStore\Pet"
                ),
                new RoutePattern(
                    "POST",
                    "/v2/pet",
                    "ByJG\RestServer\OutputProcessor\XmlOutputProcessor",
                    "addPet",
                    "PetStore\Pet"
                ),
                new RoutePattern(
                    "PUT",
                    "/v2/pet",
                    "ByJG\RestServer\OutputProcessor\JsonOutputProcessor",
                    "updatePet",
                    "PetStore\Pet"
                ),
                new RoutePattern(
                    "GET",
                    "/v2/store/inventory",
                    "ByJG\RestServer\OutputProcessor\JsonOutputProcessor",
                    "getInventory",
                    "PetStore\Pet"
                ),
                new RoutePattern(
                    "POST",
                    "/v2/store/order",
                    "ByJG\RestServer\OutputProcessor\XmlOutputProcessor",
                    "placeOrder",
                    "PetStore\Pet"
                ),
                new RoutePattern(
                    "POST",
                    "/v2/user/createWithArray",
                    "ByJG\RestServer\OutputProcessor\XmlOutputProcessor",
                    "createUsersWithArrayInput",
                    "PetStore\Pet"
                ),
                new RoutePattern(
                    "POST",
                    "/v2/user/createWithList",
                    "ByJG\RestServer\OutputProcessor\XmlOutputProcessor",
                    "createUsersWithListInput",
                    "PetStore\Pet"
                ),
                new RoutePattern(
                    "GET",
                    "/v2/user/login",
                    "ByJG\RestServer\OutputProcessor\XmlOutputProcessor",
                    "loginUser",
                    "PetStore\Pet"
                ),
                new RoutePattern(
                    "GET",
                    "/v2/user/logout",
                    "ByJG\RestServer\OutputProcessor\XmlOutputProcessor",
                    "logoutUser",
                    "PetStore\Pet"
                ),
                new RoutePattern(
                    "POST",
                    "/v2/user",
                    "ByJG\RestServer\OutputProcessor\XmlOutputProcessor",
                    "createUser",
                    "PetStore\Pet"
                ),
                new RoutePattern(
                    "POST",
                    "/v2/pet/{petId}/uploadImage",
                    "ByJG\RestServer\OutputProcessor\JsonOutputProcessor",
                    "uploadFile",
                    "PetStore\Pet"
                ),
                new RoutePattern(
                    "GET",
                    "/v2/pet/{petId}",
                    "ByJG\RestServer\OutputProcessor\JsonCleanOutputProcessor",
                    "getPetById",
                    "PetStore\Pet"
                ),
                new RoutePattern(
                    "POST",
                    "/v2/pet/{petId}",
                    "ByJG\RestServer\OutputProcessor\XmlOutputProcessor",
                    "updatePetWithForm",
                    "PetStore\Pet"
                ),
                new RoutePattern(
                    "DELETE",
                    "/v2/pet/{petId}",
                    "ByJG\RestServer\OutputProcessor\XmlOutputProcessor",
                    "deletePet",
                    "PetStore\Pet"
                ),
                new RoutePattern(
                    "GET",
                    "/v2/store/order/{orderId}",
                    "ByJG\RestServer\OutputProcessor\XmlOutputProcessor",
                    "getOrderById",
                    "PetStore\Pet"
                ),
                new RoutePattern(
                    "DELETE",
                    "/v2/store/order/{orderId}",
                    "ByJG\RestServer\OutputProcessor\XmlOutputProcessor",
                    "deleteOrder",
                    "PetStore\Pet"
                ),
                new RoutePattern(
                    "GET",
                    "/v2/user/{username}",
                    "ByJG\RestServer\OutputProcessor\XmlOutputProcessor",
                    "getUserByName",
                    "PetStore\Pet"
                ),
                new RoutePattern(
                    "PUT",
                    "/v2/user/{username}",
                    "ByJG\RestServer\OutputProcessor\XmlOutputProcessor",
                    "updateUser",
                    "PetStore\Pet"
                ),
                new RoutePattern(
                    "DELETE",
                    "/v2/user/{username}",
                    "ByJG\RestServer\OutputProcessor\XmlOutputProcessor",
                    "deleteUser",
                    "PetStore\Pet"
                ),
            ],
            $this->object->getRoutes()
        );
    }
}
