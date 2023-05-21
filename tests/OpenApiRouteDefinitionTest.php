<?php

namespace Tests;

use ByJG\RestServer\Exception\OperationIdInvalidException;
use ByJG\RestServer\Exception\SchemaInvalidException;
use ByJG\RestServer\Exception\SchemaNotFoundException;
use ByJG\RestServer\OutputProcessor\JsonCleanOutputProcessor;
use ByJG\RestServer\OutputProcessor\JsonOutputProcessor;
use ByJG\RestServer\OutputProcessor\XmlOutputProcessor;
use ByJG\RestServer\Route\Route;
use ByJG\RestServer\Route\OpenApiRouteList;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\InvalidArgumentException;

class OpenApiRouteDefinitionTest extends TestCase
{
    /**
     * @throws OperationIdInvalidException
     * @throws SchemaInvalidException
     * @throws SchemaNotFoundException
     * @throws InvalidArgumentException
     */
    public function testGenerateRoutesOpenApi2()
    {
        $object = new OpenApiRouteList(__DIR__ . '/swagger-example.json');
        $object->withOutputProcessorForRoute('get', '/v2/pet/{petId}', JsonCleanOutputProcessor::class);

        $this->assert($object);
    }

    /**
     * @throws OperationIdInvalidException
     * @throws SchemaInvalidException
     * @throws SchemaNotFoundException
     * @throws InvalidArgumentException
     */
    public function testGenerateRoutesOpenApi3()
    {
        $object = new OpenApiRouteList(__DIR__ . '/openapi-example.yaml');
        $object
            ->withOutputProcessorForRoute('get', '/v2/pet/{petId}', JsonCleanOutputProcessor::class)
            ->withDefaultProcessor(XmlOutputProcessor::class);

        $this->assert($object);
    }

    /**
     * @throws OperationIdInvalidException
     * @throws SchemaInvalidException
     * @throws SchemaNotFoundException
     * @throws InvalidArgumentException
     */
    public function testGenerateRoutesOverrideMimeOpenApi2()
    {
        $object = new OpenApiRouteList(__DIR__ . '/swagger-example.json');
        $object->withOutputProcessorForMimeType('application/xml', JsonCleanOutputProcessor::class);

        $this->assertMime($object);
    }

    /**
     * @throws OperationIdInvalidException
     * @throws SchemaInvalidException
     * @throws SchemaNotFoundException
     * @throws InvalidArgumentException
     */
    public function testGenerateRoutesOverrideMimeOpenApi3()
    {
        $object = new OpenApiRouteList(__DIR__ . '/openapi-example.yaml');

        $object
            ->withOutputProcessorForMimeType('application/xml', JsonCleanOutputProcessor::class)
            ->withDefaultProcessor(JsonCleanOutputProcessor::class);

        $this->assertMime($object);
    }

    public function testSortPaths()
    {
        // Expose the method
        $testObject = new OpenApiWrapperExposed(__DIR__ . "/swagger-example.json");

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


    protected function assert(OpenApiRouteList $object)
    {
        $this->assertEquals(
            [
                (new Route("GET", "/v2/pet/findByStatus"))
                    ->withOutputProcessor(XmlOutputProcessor::class)
                    ->withClass("PetStore\Pet", "findPetsByStatus"),
                (new Route("GET", "/v2/pet/findByTags"))
                    ->withOutputProcessor(XmlOutputProcessor::class)
                    ->withClass("PetStore\Pet", "findPetsByTags"),
                (new Route("POST", "/v2/pet"))
                    ->withOutputProcessor(XmlOutputProcessor::class)
                    ->withClass("PetStore\Pet", "addPet"),
                (new Route("PUT", "/v2/pet"))
                    ->withOutputProcessor(JsonOutputProcessor::class)
                    ->withClass("PetStore\Pet", "updatePet"),
                (new Route("GET", "/v2/store/inventory"))
                    ->withOutputProcessor(JsonOutputProcessor::class)
                    ->withClass("PetStore\Pet", "getInventory"),
                (new Route("POST", "/v2/store/order"))
                    ->withOutputProcessor(XmlOutputProcessor::class)
                    ->withClass("PetStore\Pet", "placeOrder"),
                (new Route("POST", "/v2/user/createWithArray"))
                    ->withOutputProcessor(XmlOutputProcessor::class)
                    ->withClass("PetStore\Pet", "createUsersWithArrayInput"),
                (new Route("POST", "/v2/user/createWithList"))
                    ->withOutputProcessor(XmlOutputProcessor::class)
                    ->withClass("PetStore\Pet", "createUsersWithListInput"),
                (new Route("GET", "/v2/user/login"))
                    ->withOutputProcessor(XmlOutputProcessor::class)
                    ->withClass("PetStore\Pet", "loginUser"),
                (new Route("GET", "/v2/user/logout"))
                    ->withOutputProcessor(XmlOutputProcessor::class)
                    ->withClass("PetStore\Pet", "logoutUser"),
                (new Route("POST", "/v2/user"))
                    ->withOutputProcessor(XmlOutputProcessor::class)
                    ->withClass("PetStore\Pet", "createUser"),
                (new Route("POST", "/v2/pet/{petId}/uploadImage"))
                    ->withOutputProcessor(JsonOutputProcessor::class)
                    ->withClass("PetStore\Pet", "uploadFile"),
                (new Route("GET", "/v2/pet/{petId}"))
                    ->withOutputProcessor(JsonCleanOutputProcessor::class)
                    ->withClass("PetStore\Pet", "getPetById"),
                (new Route("POST", "/v2/pet/{petId}"))
                    ->withOutputProcessor(XmlOutputProcessor::class)
                    ->withClass("PetStore\Pet", "updatePetWithForm"),
                (new Route("DELETE", "/v2/pet/{petId}"))
                    ->withOutputProcessor(XmlOutputProcessor::class)
                    ->withClass("PetStore\Pet", "deletePet"),
                (new Route("GET", "/v2/store/order/{orderId}"))
                    ->withOutputProcessor(XmlOutputProcessor::class)
                    ->withClass("PetStore\Pet", "getOrderById"),
                (new Route("DELETE", "/v2/store/order/{orderId}"))
                    ->withOutputProcessor(XmlOutputProcessor::class)
                    ->withClass("PetStore\Pet", "deleteOrder"),
                (new Route("GET", "/v2/user/{username}"))
                    ->withOutputProcessor(XmlOutputProcessor::class)
                    ->withClass("PetStore\Pet", "getUserByName"),
                (new Route("PUT", "/v2/user/{username}"))
                    ->withOutputProcessor(XmlOutputProcessor::class)
                    ->withClass("PetStore\Pet", "updateUser"),
                (new Route("DELETE", "/v2/user/{username}"))
                    ->withOutputProcessor(XmlOutputProcessor::class)
                    ->withClass("PetStore\Pet", "deleteUser"),
            ],
            $object->getRoutes()
        );
    }

    protected function assertMime(OpenApiRouteList $object)
    {
        $this->assertEquals(
            [
                (new Route("GET", "/v2/pet/findByStatus"))
                    ->withOutputProcessor(JsonCleanOutputProcessor::class)
                    ->withClass("PetStore\Pet", "findPetsByStatus"),
                (new Route("GET", "/v2/pet/findByTags"))
                    ->withOutputProcessor(JsonCleanOutputProcessor::class)
                    ->withClass("PetStore\Pet", "findPetsByTags"),
                (new Route("POST", "/v2/pet"))
                    ->withOutputProcessor(JsonCleanOutputProcessor::class)
                    ->withClass("PetStore\Pet", "addPet"),
                (new Route("PUT", "/v2/pet"))
                    ->withOutputProcessor(JsonOutputProcessor::class)
                    ->withClass("PetStore\Pet", "updatePet"),
                (new Route("GET", "/v2/store/inventory"))
                    ->withOutputProcessor(JsonOutputProcessor::class)
                    ->withClass("PetStore\Pet", "getInventory"),
                (new Route("POST", "/v2/store/order"))
                    ->withOutputProcessor(JsonCleanOutputProcessor::class)
                    ->withClass("PetStore\Pet", "placeOrder"),
                (new Route("POST", "/v2/user/createWithArray"))
                    ->withOutputProcessor(JsonCleanOutputProcessor::class)
                    ->withClass("PetStore\Pet", "createUsersWithArrayInput"),
                (new Route("POST", "/v2/user/createWithList"))
                    ->withOutputProcessor(JsonCleanOutputProcessor::class)
                    ->withClass("PetStore\Pet", "createUsersWithListInput"),
                (new Route("GET", "/v2/user/login"))
                    ->withOutputProcessor(JsonCleanOutputProcessor::class)
                    ->withClass("PetStore\Pet", "loginUser"),
                (new Route("GET", "/v2/user/logout"))
                    ->withOutputProcessor(JsonCleanOutputProcessor::class)
                    ->withClass("PetStore\Pet", "logoutUser"),
                (new Route("POST", "/v2/user"))
                    ->withOutputProcessor(JsonCleanOutputProcessor::class)
                    ->withClass("PetStore\Pet", "createUser"),
                (new Route("POST", "/v2/pet/{petId}/uploadImage"))
                    ->withOutputProcessor(JsonOutputProcessor::class)
                    ->withClass("PetStore\Pet", "uploadFile"),
                (new Route("GET", "/v2/pet/{petId}"))
                    ->withOutputProcessor(JsonOutputProcessor::class)
                    ->withClass("PetStore\Pet", "getPetById"),
                (new Route("POST", "/v2/pet/{petId}"))
                    ->withOutputProcessor(JsonCleanOutputProcessor::class)
                    ->withClass("PetStore\Pet", "updatePetWithForm"),
                (new Route("DELETE", "/v2/pet/{petId}"))
                    ->withOutputProcessor(JsonCleanOutputProcessor::class)
                    ->withClass("PetStore\Pet", "deletePet"),
                (new Route("GET", "/v2/store/order/{orderId}"))
                    ->withOutputProcessor(JsonCleanOutputProcessor::class)
                    ->withClass("PetStore\Pet", "getOrderById"),
                (new Route("DELETE", "/v2/store/order/{orderId}"))
                    ->withOutputProcessor(JsonCleanOutputProcessor::class)
                    ->withClass("PetStore\Pet", "deleteOrder"),
                (new Route("GET", "/v2/user/{username}"))
                    ->withOutputProcessor(JsonCleanOutputProcessor::class)
                    ->withClass("PetStore\Pet", "getUserByName"),
                (new Route("PUT", "/v2/user/{username}"))
                    ->withOutputProcessor(JsonCleanOutputProcessor::class)
                    ->withClass("PetStore\Pet", "updateUser"),
                (new Route("DELETE", "/v2/user/{username}"))
                    ->withOutputProcessor(JsonCleanOutputProcessor::class)
                    ->withClass("PetStore\Pet", "deleteUser"),
            ],
            $object->getRoutes()
        );
    }
}
