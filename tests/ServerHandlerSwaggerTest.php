<?php

namespace Tests;

use ByJG\RestServer\RoutePattern;
use ByJG\RestServer\ServerRequestHandler;

// backward compatibility
if (!class_exists('\PHPUnit\Framework\TestCase')) {
    class_alias('\PHPUnit_Framework_TestCase', '\PHPUnit\Framework\TestCase');
}

class ServerHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \ByJG\RestServer\ServerRequestHandler
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
     * @throws \ByJG\RestServer\Exception\OperationIdInvalidException
     * @throws \ByJG\RestServer\Exception\SchemaInvalidException
     * @throws \ByJG\RestServer\Exception\SchemaNotFoundException
     */
    public function testGenerateRoutes()
    {
        $this->object->setRoutesSwagger(__DIR__ . '/swagger-example.json');

        $this->assertEquals(
            [
                new RoutePattern(
                    "POST",
                    "/pet",
                    "ByJG\RestServer\HandleOutput\XmlHandler",
                    "addPet",
                    "PetStore\Pet"
                ),

                new RoutePattern(
                    "PUT",
                    "/pet",
                    "ByJG\RestServer\HandleOutput\XmlHandler",
                    "updatePet",
                    "PetStore\Pet"
                ),

                new RoutePattern(
                    "GET",
                    "/pet/findByStatus",
                    "ByJG\RestServer\HandleOutput\XmlHandler",
                    "findPetsByStatus",
                    "PetStore\Pet"
                ),

                new RoutePattern(
                    "GET",
                    "/pet/findByTags",
                    "ByJG\RestServer\HandleOutput\XmlHandler",
                    "findPetsByTags",
                    "PetStore\Pet"
                ),

                new RoutePattern(
                    "GET",
                    "/pet/{petId}",
                    "ByJG\RestServer\HandleOutput\XmlHandler",
                    "getPetById",
                    "PetStore\Pet"
                ),

                new RoutePattern(
                    "POST",
                    "/pet/{petId}",
                    "ByJG\RestServer\HandleOutput\XmlHandler",
                    "updatePetWithForm",
                    "PetStore\Pet"
                ),

                new RoutePattern(
                    "DELETE",
                    "/pet/{petId}",
                    "ByJG\RestServer\HandleOutput\XmlHandler",
                    "deletePet",
                    "PetStore\Pet"
                ),

                new RoutePattern(
                    "POST",
                    "/pet/{petId}/uploadImage",
                    "ByJG\RestServer\HandleOutput\JsonHandler",
                    "uploadFile",
                    "PetStore\Pet"
                ),

                new RoutePattern(
                    "GET",
                    "/store/inventory",
                    "ByJG\RestServer\HandleOutput\JsonHandler",
                    "getInventory",
                    "PetStore\Pet"
                ),

                new RoutePattern(
                    "POST",
                    "/store/order",
                    "ByJG\RestServer\HandleOutput\XmlHandler",
                    "placeOrder",
                    "PetStore\Pet"
                ),

                new RoutePattern(
                    "GET",
                    "/store/order/{orderId}",
                    "ByJG\RestServer\HandleOutput\XmlHandler",
                    "getOrderById",
                    "PetStore\Pet"
                ),

                new RoutePattern(
                    "DELETE",
                    "/store/order/{orderId}",
                    "ByJG\RestServer\HandleOutput\XmlHandler",
                    "deleteOrder",
                    "PetStore\Pet"
                ),

                new RoutePattern(
                    "POST",
                    "/user",
                    "ByJG\RestServer\HandleOutput\XmlHandler",
                    "createUser",
                    "PetStore\Pet"
                ),

                new RoutePattern(
                    "POST",
                    "/user/createWithArray",
                    "ByJG\RestServer\HandleOutput\XmlHandler",
                    "createUsersWithArrayInput",
                    "PetStore\Pet"
                ),

                new RoutePattern(
                    "POST",
                    "/user/createWithList",
                    "ByJG\RestServer\HandleOutput\XmlHandler",
                    "createUsersWithListInput",
                    "PetStore\Pet"
                ),

                new RoutePattern(
                    "GET",
                    "/user/login",
                    "ByJG\RestServer\HandleOutput\XmlHandler",
                    "loginUser",
                    "PetStore\Pet"
                ),

                new RoutePattern(
                    "GET",
                    "/user/logout",
                    "ByJG\RestServer\HandleOutput\XmlHandler",
                    "logoutUser",
                    "PetStore\Pet"
                ),

                new RoutePattern(
                    "GET",
                    "/user/{username}",
                    "ByJG\RestServer\HandleOutput\XmlHandler",
                    "getUserByName",
                    "PetStore\Pet"
                ),

                new RoutePattern(
                    "PUT",
                    "/user/{username}",
                    "ByJG\RestServer\HandleOutput\XmlHandler",
                    "updateUser",
                    "PetStore\Pet"
                ),

                new RoutePattern(
                    "DELETE",
                    "/user/{username}",
                    "ByJG\RestServer\HandleOutput\XmlHandler",
                    "deleteUser",
                    "PetStore\Pet"
                ),

            ],
            $this->object->getRoutes()
        );
    }
}
