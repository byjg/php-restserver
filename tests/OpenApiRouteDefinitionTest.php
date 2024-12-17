<?php

namespace Tests;

use ByJG\RestServer\Exception\OperationIdInvalidException;
use ByJG\RestServer\Exception\SchemaInvalidException;
use ByJG\RestServer\Exception\SchemaNotFoundException;
use ByJG\RestServer\OutputProcessor\JsonCleanOutputProcessor;
use ByJG\RestServer\OutputProcessor\JsonOutputProcessor;
use ByJG\RestServer\OutputProcessor\XmlOutputProcessor;
use ByJG\RestServer\Route\OpenApiRouteList;
use ByJG\RestServer\Route\Route;
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
    public function testGenerateRoutesOpenApi2(): void
    {
        $object = new OpenApiRouteList(__DIR__ . '/fixtures/swagger-example.json');
        $object->withOutputProcessorForRoute('get', '/v2/pet/{petId}', JsonCleanOutputProcessor::class);

        $this->assert($object);
    }

    /**
     * @throws OperationIdInvalidException
     * @throws SchemaInvalidException
     * @throws SchemaNotFoundException
     * @throws InvalidArgumentException
     */
    public function testGenerateRoutesOpenApi3(): void
    {
        $object = new OpenApiRouteList(__DIR__ . '/fixtures/openapi-example.yaml');
        $object
            ->withOutputProcessorForRoute('get', '/v2/pet/{petId}', JsonCleanOutputProcessor::class)
            ->withDefaultProcessor(XmlOutputProcessor::class);

        $this->assert($object);
    }

    public function testGetRouteWithPathParam(): void
    {
        $object = new OpenApiRouteList(__DIR__ . '/fixtures/openapi-example.yaml');

        $routeWithoutPathParam = $object->getRoute('get', '/v2/pet/findByTags');
        $this->assertNotEmpty($routeWithoutPathParam);
        $this->assertEquals('/v2/pet/findByTags', $routeWithoutPathParam->getPath());

        $routeWithPathParam = $object->getRoute('get', '/v2/pet/1');
        $this->assertNotEmpty($routeWithPathParam);
        $this->assertEquals('/v2/pet/{petId}', $routeWithPathParam->getPath());
        $this->assertEquals('GET', $routeWithPathParam->getMethod());


        $routeWithPathParam = $object->getRoute('post', '/v2/pet/find');
        $this->assertNotEmpty($routeWithPathParam);
        $this->assertEquals('/v2/pet/{petId}', $routeWithPathParam->getPath());
        $this->assertEquals('POST', $routeWithPathParam->getMethod());
    }

    /**
     * @throws OperationIdInvalidException
     * @throws SchemaInvalidException
     * @throws SchemaNotFoundException
     * @throws InvalidArgumentException
     */
    public function testGenerateRoutesOverrideMimeOpenApi2(): void
    {
        $object = new OpenApiRouteList(__DIR__ . '/fixtures/swagger-example.json');
        $object->withOutputProcessorForMimeType('application/xml', JsonCleanOutputProcessor::class);

        $this->assertMime($object);
    }

    /**
     * @throws OperationIdInvalidException
     * @throws SchemaInvalidException
     * @throws SchemaNotFoundException
     * @throws InvalidArgumentException
     */
    public function testGenerateRoutesOverrideMimeOpenApi3(): void
    {
        $object = new OpenApiRouteList(__DIR__ . '/fixtures/openapi-example.yaml');

        $object
            ->withOutputProcessorForMimeType('application/xml', JsonCleanOutputProcessor::class)
            ->withDefaultProcessor(JsonCleanOutputProcessor::class);

        $this->assertMime($object);
    }

    public function testSortPaths(): void
    {
        // Expose the method
        $testObject = new OpenApiWrapperExposed(__DIR__ . "/fixtures/swagger-example.json");

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


    protected function assert(OpenApiRouteList $object): void
    {
        $this->assertEquals(
            [
                (new Route("GET", "/v2/pet/findByStatus"))
                    ->withOutputProcessor(XmlOutputProcessor::class)
                    ->withClass("PetStore\Pet", "findPetsByStatus")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/pet/findByStatus"
                    ]),
                (new Route("GET", "/v2/pet/findByTags"))
                    ->withOutputProcessor(XmlOutputProcessor::class)
                    ->withClass("PetStore\Pet", "findPetsByTags")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/pet/findByTags"
                    ]),
                (new Route("POST", "/v2/pet"))
                    ->withOutputProcessor(XmlOutputProcessor::class)
                    ->withClass("PetStore\Pet", "addPet")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/pet"
                    ]),
                (new Route("PUT", "/v2/pet"))
                    ->withOutputProcessor(JsonOutputProcessor::class)
                    ->withClass("PetStore\Pet", "updatePet")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/pet"
                    ]),
                (new Route("GET", "/v2/store/inventory"))
                    ->withOutputProcessor(JsonOutputProcessor::class)
                    ->withClass("PetStore\Pet", "getInventory")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/store/inventory"
                    ]),
                (new Route("POST", "/v2/store/order"))
                    ->withOutputProcessor(XmlOutputProcessor::class)
                    ->withClass("PetStore\Pet", "placeOrder")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/store/order"
                    ]),
                (new Route("POST", "/v2/user/createWithArray"))
                    ->withOutputProcessor(XmlOutputProcessor::class)
                    ->withClass("PetStore\Pet", "createUsersWithArrayInput")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/user/createWithArray"
                    ]),
                (new Route("POST", "/v2/user/createWithList"))
                    ->withOutputProcessor(XmlOutputProcessor::class)
                    ->withClass("PetStore\Pet", "createUsersWithListInput")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/user/createWithList"
                    ]),
                (new Route("GET", "/v2/user/login"))
                    ->withOutputProcessor(XmlOutputProcessor::class)
                    ->withClass("PetStore\Pet", "loginUser")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/user/login"
                    ]),
                (new Route("GET", "/v2/user/logout"))
                    ->withOutputProcessor(XmlOutputProcessor::class)
                    ->withClass("PetStore\Pet", "logoutUser")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/user/logout"
                    ]),
                (new Route("POST", "/v2/user"))
                    ->withOutputProcessor(XmlOutputProcessor::class)
                    ->withClass("PetStore\Pet", "createUser")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/user"
                    ]),
                (new Route("POST", "/v2/pet/{petId}/uploadImage"))
                    ->withOutputProcessor(JsonOutputProcessor::class)
                    ->withClass("PetStore\Pet", "uploadFile")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/pet/{petId}/uploadImage"
                    ]),
                (new Route("GET", "/v2/pet/{petId}"))
                    ->withOutputProcessor(JsonCleanOutputProcessor::class)
                    ->withClass("PetStore\Pet", "getPetById")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/pet/{petId}"
                    ]),
                (new Route("POST", "/v2/pet/{petId}"))
                    ->withOutputProcessor(XmlOutputProcessor::class)
                    ->withClass("PetStore\Pet", "updatePetWithForm")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/pet/{petId}"
                    ]),
                (new Route("DELETE", "/v2/pet/{petId}"))
                    ->withOutputProcessor(XmlOutputProcessor::class)
                    ->withClass("PetStore\Pet", "deletePet")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/pet/{petId}"
                    ]),
                (new Route("GET", "/v2/store/order/{orderId}"))
                    ->withOutputProcessor(XmlOutputProcessor::class)
                    ->withClass("PetStore\Pet", "getOrderById")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/store/order/{orderId}"
                    ]),
                (new Route("DELETE", "/v2/store/order/{orderId}"))
                    ->withOutputProcessor(XmlOutputProcessor::class)
                    ->withClass("PetStore\Pet", "deleteOrder")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/store/order/{orderId}"
                    ]),
                (new Route("GET", "/v2/user/{username}"))
                    ->withOutputProcessor(XmlOutputProcessor::class)
                    ->withClass("PetStore\Pet", "getUserByName")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/user/{username}"
                    ]),
                (new Route("PUT", "/v2/user/{username}"))
                    ->withOutputProcessor(XmlOutputProcessor::class)
                    ->withClass("PetStore\Pet", "updateUser")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/user/{username}"
                    ]),
                (new Route("DELETE", "/v2/user/{username}"))
                    ->withOutputProcessor(XmlOutputProcessor::class)
                    ->withClass("PetStore\Pet", "deleteUser")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/user/{username}"
                    ]),
            ],
            $object->getRoutes()
        );
    }

    protected function assertMime(OpenApiRouteList $object): void
    {
        $this->assertEquals(
            [
                (new Route("GET", "/v2/pet/findByStatus"))
                    ->withOutputProcessor(JsonCleanOutputProcessor::class)
                    ->withClass("PetStore\Pet", "findPetsByStatus")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/pet/findByStatus"
                    ]),
                (new Route("GET", "/v2/pet/findByTags"))
                    ->withOutputProcessor(JsonCleanOutputProcessor::class)
                    ->withClass("PetStore\Pet", "findPetsByTags")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/pet/findByTags"
                    ]),
                (new Route("POST", "/v2/pet"))
                    ->withOutputProcessor(JsonCleanOutputProcessor::class)
                    ->withClass("PetStore\Pet", "addPet")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/pet"
                    ]),
                (new Route("PUT", "/v2/pet"))
                    ->withOutputProcessor(JsonOutputProcessor::class)
                    ->withClass("PetStore\Pet", "updatePet")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/pet"
                    ]),
                (new Route("GET", "/v2/store/inventory"))
                    ->withOutputProcessor(JsonOutputProcessor::class)
                    ->withClass("PetStore\Pet", "getInventory")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/store/inventory"
                    ]),
                (new Route("POST", "/v2/store/order"))
                    ->withOutputProcessor(JsonCleanOutputProcessor::class)
                    ->withClass("PetStore\Pet", "placeOrder")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/store/order"
                    ]),
                (new Route("POST", "/v2/user/createWithArray"))
                    ->withOutputProcessor(JsonCleanOutputProcessor::class)
                    ->withClass("PetStore\Pet", "createUsersWithArrayInput")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/user/createWithArray"
                    ]),
                (new Route("POST", "/v2/user/createWithList"))
                    ->withOutputProcessor(JsonCleanOutputProcessor::class)
                    ->withClass("PetStore\Pet", "createUsersWithListInput")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/user/createWithList"
                    ]),
                (new Route("GET", "/v2/user/login"))
                    ->withOutputProcessor(JsonCleanOutputProcessor::class)
                    ->withClass("PetStore\Pet", "loginUser")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/user/login"
                    ]),
                (new Route("GET", "/v2/user/logout"))
                    ->withOutputProcessor(JsonCleanOutputProcessor::class)
                    ->withClass("PetStore\Pet", "logoutUser")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/user/logout"
                    ]),
                (new Route("POST", "/v2/user"))
                    ->withOutputProcessor(JsonCleanOutputProcessor::class)
                    ->withClass("PetStore\Pet", "createUser")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/user"
                    ]),
                (new Route("POST", "/v2/pet/{petId}/uploadImage"))
                    ->withOutputProcessor(JsonOutputProcessor::class)
                    ->withClass("PetStore\Pet", "uploadFile")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/pet/{petId}/uploadImage"
                    ]),
                (new Route("GET", "/v2/pet/{petId}"))
                    ->withOutputProcessor(JsonOutputProcessor::class)
                    ->withClass("PetStore\Pet", "getPetById")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/pet/{petId}"
                    ]),
                (new Route("POST", "/v2/pet/{petId}"))
                    ->withOutputProcessor(JsonCleanOutputProcessor::class)
                    ->withClass("PetStore\Pet", "updatePetWithForm")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/pet/{petId}"
                    ]),
                (new Route("DELETE", "/v2/pet/{petId}"))
                    ->withOutputProcessor(JsonCleanOutputProcessor::class)
                    ->withClass("PetStore\Pet", "deletePet")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/pet/{petId}"
                    ]),
                (new Route("GET", "/v2/store/order/{orderId}"))
                    ->withOutputProcessor(JsonCleanOutputProcessor::class)
                    ->withClass("PetStore\Pet", "getOrderById")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/store/order/{orderId}"
                    ]),
                (new Route("DELETE", "/v2/store/order/{orderId}"))
                    ->withOutputProcessor(JsonCleanOutputProcessor::class)
                    ->withClass("PetStore\Pet", "deleteOrder")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/store/order/{orderId}"
                    ]),
                (new Route("GET", "/v2/user/{username}"))
                    ->withOutputProcessor(JsonCleanOutputProcessor::class)
                    ->withClass("PetStore\Pet", "getUserByName")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/user/{username}"
                    ]),
                (new Route("PUT", "/v2/user/{username}"))
                    ->withOutputProcessor(JsonCleanOutputProcessor::class)
                    ->withClass("PetStore\Pet", "updateUser")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/user/{username}"
                    ]),
                (new Route("DELETE", "/v2/user/{username}"))
                    ->withOutputProcessor(JsonCleanOutputProcessor::class)
                    ->withClass("PetStore\Pet", "deleteUser")
                    ->withMetadata([
                        OpenApiRouteList::OPENAPI_BASE_PATH => "/v2",
                        OpenApiRouteList::OPENAPI_PATH => "/user/{username}"
                    ]),
            ],
            $object->getRoutes()
        );
    }
}
