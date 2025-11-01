<?php

namespace Tests;

use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\ResponseBag;
use Override;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

class HttpResponseTest extends TestCase
{

    /**
     * @var HttpResponse
     */
    protected $object;

    #[Override]
    public function setup(): void
    {
        $this->object = new HttpResponse();
    }

    #[Override]
    public function tearDown(): void
    {
        $this->object = null;
    }

    public function testGetHeaders(): void
    {
        $this->assertEquals(
            [],
            $this->object->getHeaders()
        );

        $this->object->addHeader('X-Test', 'OK');

        $this->assertEquals(
            [
                'X-Test' => 'OK',
            ],
            $this->object->getHeaders()
        );

        $this->object->addHeader('X-Test2', 'OK2');

        $this->assertEquals(
            [
                'X-Test' => 'OK',
                'X-Test2' => 'OK2',
            ],
            $this->object->getHeaders()
        );

        $this->object->addHeader('X-Test', 'OK3');

        $this->assertEquals(
            [
                'X-Test' => 'OK3',
                'X-Test2' => 'OK2',
            ],
            $this->object->getHeaders()
        );

        $this->object->addHeader('X-Test2', ['value1', 'value2']);

        $this->assertEquals(
            [
                'X-Test' => 'OK3',
                'X-Test2' => ['value1', 'value2'],
            ],
            $this->object->getHeaders()
        );
    }

    public function testSetResponseCode(): void
    {
        $this->assertEquals(
            200,
            $this->object->getResponseCode()
        );

        $this->object->setResponseCode(302);

        $this->assertEquals(
            302,
            $this->object->getResponseCode()
        );
    }

    public function testGetResponseCodeDescription(): void
    {
        $this->assertEquals(
            'OK',
            $this->object->getResponseCodeDescription()
        );

        $this->object->setResponseCode(302, 'Found');

        $this->assertEquals(
            'Found',
            $this->object->getResponseCodeDescription()
        );

        // Test with default description (empty string)
        $this->object->setResponseCode(404);

        $this->assertEquals(
            '',
            $this->object->getResponseCodeDescription()
        );
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testSessionManagement(): void
    {
        // Setup session for testing
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Clear any existing session data
        $_SESSION = [];

        // Test setSession
        $this->object->setSession('test_key', 'test_value');
        /** @psalm-suppress EmptyArrayAccess */
        $this->assertEquals('test_value', $_SESSION['test_key']);

        // Test removeSession
        $this->object->removeSession('test_key');
        $this->assertArrayNotHasKey('test_key', $_SESSION);

        // Clean up
        session_write_close();
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testCookieManagement(): void
    {
        // Since we can't test actual cookies in PHPUnit easily,
        // we'll use runkit_function_redefine or similar approach in a real environment
        // Here we'll just verify the code doesn't throw exceptions

        // Clear any existing cookie data for testing
        $_COOKIE = [];

        // Test addCookie (can't verify actual cookie, just make sure it doesn't throw)
        $this->object->addCookie('test_cookie', 'cookie_value');
        $this->object->addCookie('test_cookie2', 'cookie_value2', 3600, '/', 'example.com');

        // Test removeCookie
        $this->object->removeCookie('test_cookie');
        $this->assertArrayNotHasKey('test_cookie', $_COOKIE);
    }

    public function testResponseBagAndWrite(): void
    {
        // Test getResponseBag
        $responseBag = $this->object->getResponseBag();
        $this->assertInstanceOf(ResponseBag::class, $responseBag);

        // Test write method
        $testData = ['key' => 'value'];
        $this->object->write($testData);

        // Verify data was added to the response bag
        $processedData = $responseBag->process();
        $this->assertEquals($testData, $processedData);

        // Test writing multiple items
        $testData2 = ['key2' => 'value2'];
        $this->object->write($testData2);

        // Verify both items are in the response
        $processedData = $responseBag->process();
        $this->assertEquals([$testData, $testData2], $processedData);

        // Test emptyResponse
        $this->object->emptyResponse();
        $processedData = $this->object->getResponseBag()->process();
        $this->assertEquals([], $processedData);
    }

    public function testWriteDebug(): void
    {
        // Test writeDebug method
        $debugKey = 'debug_key';
        $debugValue = 'debug_value';
        $this->object->writeDebug($debugKey, $debugValue);

        // Get the processed response
        $processedData = $this->object->getResponseBag()->process();

        // Verify data was added to the response bag
        $this->assertIsArray($processedData);
        $this->assertArrayHasKey("collection", $processedData);
        $processedData = $processedData["collection"];

        // The debug data should be in the response
        $this->assertIsArray($processedData);
        $this->assertArrayHasKey(0, $processedData);

        // The debug data structure should match what we expect
        $debugData = $processedData[0];
        $this->assertIsArray($debugData);
        $this->assertArrayHasKey('debug', $debugData);
        $this->assertIsArray($debugData['debug']);
        $this->assertArrayHasKey($debugKey, $debugData['debug']);
        $this->assertEquals($debugValue, $debugData['debug'][$debugKey]);

        // Test multiple debug entries
        $debugKey2 = 'debug_key2';
        $debugValue2 = ['complex' => 'value'];
        $this->object->writeDebug($debugKey2, $debugValue2);

        // Get the processed response again
        $processedData = $this->object->getResponseBag()->process();

        // Verify both debug entries are present
        $this->assertCount(2, $processedData);
    }
}
