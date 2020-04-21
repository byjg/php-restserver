<?php

namespace Tests;

use ByJG\RestServer\Exception\OperationIdInvalidException;
use ByJG\RestServer\Exception\SchemaInvalidException;
use ByJG\RestServer\Exception\SchemaNotFoundException;
use ByJG\RestServer\OutputProcessor\JsonCleanOutputProcessor;
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
        $object = new SwaggerRouteDefinition(__DIR__ . '/swagger-example.json');
        $object->withOutputProcessorFor('get', '/v2/pet/{petId}', JsonCleanOutputProcessor::class);

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
        $object = new SwaggerRouteDefinition(__DIR__ . '/openapi-example.json', XmlOutputProcessor::class);
        $object->withOutputProcessorFor('get', '/v2/pet/{petId}', JsonCleanOutputProcessor::class);

        $this->assert($object);
    }

    public function testSortPaths()
    {
        // Expose the method
        $testObject = new SwaggerWrapperExposed(__DIR__ . "/swagger-example.json", null);

        $pathList = [
            "/rest/accessible/recentPosts",
            "/rest/accessible/postsWithFilter",
            "/rest/audio/{id}",
            "/rest/audio/all",
            "/rest/audio",
            "/rest/audio/upload",
            "/rest/backgroundaudio/{id}",
            "/rest/backgroundaudio/all",
            "/rest/backgroundaudio",
            "/rest/blog/{id}",
            "/rest/blog/all",
            "/rest/blog",
            "/rest/dictionary/{id}",
            "/rest/dictionary/all",
            "/rest/dictionary",
            "/rest/registerblog/tts",
            "/rest/registerblog/availlang",
            "/rest/registerblog/platforms",
            "/rest/registerblog/sanitizewpurl",
            "/rest/registerblog/checkplugin",
            "/rest/registerblog/checkfeed",
            "/rest/login",
            "/rest/narrator/{id}",
            "/rest/narrator/{id:unique}",
            "/rest/narrator/all",
            "/rest/narrator",
            "/rest/newsletter/email",
            "/rest/platform/{id}",
            "/rest/platform/all",
            "/rest/platform",
            "/rest/post/{id}",
            "/rest/post/all",
            "/rest/post",
            "/rest/post/activeaudio/{id}",
            "/rest/audiowidget/{objectid}",
            "/rest/audiowidget/blog",
            "/rest/audiowidget/send",
            "/rest/audiowidget/post/{blogId}",
            "/rest/audiowidget/notify/{blogId}/{event}",
            "/rest/logplayer",
        ];

        $pathResult = $testObject->sortPaths($pathList);

        $this->assertEquals(
            [
                "/rest/accessible/postsWithFilter",
                "/rest/accessible/recentPosts",
                "/rest/audio/all",
                "/rest/audio/upload",
                "/rest/audiowidget/blog",
                "/rest/audiowidget/send",
                "/rest/audio",
                "/rest/backgroundaudio/all",
                "/rest/backgroundaudio",
                "/rest/blog/all",
                "/rest/blog",
                "/rest/dictionary/all",
                "/rest/dictionary",
                "/rest/login",
                "/rest/logplayer",
                "/rest/narrator/all",
                "/rest/narrator",
                "/rest/newsletter/email",
                "/rest/platform/all",
                "/rest/platform",
                "/rest/post/all",
                "/rest/post",
                "/rest/registerblog/availlang",
                "/rest/registerblog/checkfeed",
                "/rest/registerblog/checkplugin",
                "/rest/registerblog/platforms",
                "/rest/registerblog/sanitizewpurl",
                "/rest/registerblog/tts",
                "/rest/audio/{id}",
                "/rest/audiowidget/notify/{blogId}/{event}",
                "/rest/audiowidget/post/{blogId}",
                "/rest/audiowidget/{objectid}",
                "/rest/backgroundaudio/{id}",
                "/rest/blog/{id}",
                "/rest/dictionary/{id}",
                '/rest/narrator/{id:unique}',
                "/rest/narrator/{id}",
                "/rest/platform/{id}",
                "/rest/post/activeaudio/{id}",
                "/rest/post/{id}",
            ],
            $pathResult
        );
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
