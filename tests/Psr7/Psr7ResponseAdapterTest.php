<?php

namespace Tests\Psr7;

use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\Psr7\Psr7ResponseAdapter;
use ByJG\WebRequest\Psr7\MemoryStream;
use ByJG\WebRequest\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class Psr7ResponseAdapterTest extends TestCase
{
    public function testFromHttpResponseBasic(): void
    {
        $httpResponse = new HttpResponse();
        $httpResponse->write(['status' => 'success', 'data' => ['id' => 1]]);
        $httpResponse->setResponseCode(200);

        $psr7Response = Psr7ResponseAdapter::fromHttpResponse($httpResponse);

        $this->assertInstanceOf(ResponseInterface::class, $psr7Response);
        $this->assertEquals(200, $psr7Response->getStatusCode());
    }

    public function testFromHttpResponseWithCustomStatusCode(): void
    {
        $httpResponse = new HttpResponse();
        $httpResponse->setResponseCode(404, 'Not Found');
        $httpResponse->write(['error' => 'Resource not found']);

        $psr7Response = Psr7ResponseAdapter::fromHttpResponse($httpResponse);

        $this->assertEquals(404, $psr7Response->getStatusCode());
        $this->assertEquals('Not Found', $psr7Response->getReasonPhrase());
    }

    public function testFromHttpResponseHeaders(): void
    {
        $httpResponse = new HttpResponse();
        $httpResponse->addHeader('X-Custom-Header', 'custom-value');
        $httpResponse->addHeader('X-Rate-Limit', '100');
        $httpResponse->write(['data' => 'test']);

        $psr7Response = Psr7ResponseAdapter::fromHttpResponse($httpResponse);

        $this->assertEquals(['custom-value'], $psr7Response->getHeader('X-Custom-Header'));
        $this->assertEquals(['100'], $psr7Response->getHeader('X-Rate-Limit'));
    }

    public function testFromHttpResponseContentType(): void
    {
        $httpResponse = new HttpResponse();
        $httpResponse->write(['test' => 'data']);

        // Test default JSON content type
        $psr7Response = Psr7ResponseAdapter::fromHttpResponse($httpResponse);
        $this->assertEquals('application/json', $psr7Response->getHeaderLine('Content-Type'));

        // Test custom content type
        $httpResponse2 = new HttpResponse();
        $httpResponse2->addHeader('Content-Type', 'text/xml');
        $httpResponse2->write('<root></root>');

        $psr7Response2 = Psr7ResponseAdapter::fromHttpResponse($httpResponse2);
        $this->assertEquals('text/xml', $psr7Response2->getHeaderLine('Content-Type'));
    }

    public function testFromHttpResponseJsonBody(): void
    {
        $httpResponse = new HttpResponse();
        $data = ['id' => 123, 'name' => 'Test', 'active' => true];
        $httpResponse->write($data);

        $psr7Response = Psr7ResponseAdapter::fromHttpResponse($httpResponse);

        $body = $psr7Response->getBody()->__toString();
        $decoded = json_decode($body, true);

        $this->assertEquals($data, $decoded);
    }

    public function testFromHttpResponseEmptyBody(): void
    {
        $httpResponse = new HttpResponse();
        $httpResponse->setResponseCode(204);

        $psr7Response = Psr7ResponseAdapter::fromHttpResponse($httpResponse);

        $body = $psr7Response->getBody()->__toString();
        $this->assertEquals('', $body);
    }

    public function testFromHttpResponseMultipleWrites(): void
    {
        $httpResponse = new HttpResponse();
        $httpResponse->write(['first' => 'data']);
        $httpResponse->write(['second' => 'data']);

        $psr7Response = Psr7ResponseAdapter::fromHttpResponse($httpResponse);

        $body = $psr7Response->getBody()->__toString();
        $decoded = json_decode($body, true);

        $this->assertIsArray($decoded);
        $this->assertCount(2, $decoded);
    }

    public function testToHttpResponseBasic(): void
    {
        $psr7Response = new Response(200);
        $psr7Response = $psr7Response->withHeader('Content-Type', 'application/json');
        $psr7Response = $psr7Response->withBody(
            new MemoryStream('{"status": "success"}')
        );

        $httpResponse = Psr7ResponseAdapter::toHttpResponse($psr7Response);

        $this->assertInstanceOf(HttpResponse::class, $httpResponse);
        $this->assertEquals(200, $httpResponse->getResponseCode());
    }

    public function testToHttpResponseWithStatusAndReason(): void
    {
        $psr7Response = new Response(404);
        $psr7Response = $psr7Response->withStatus(404, 'Resource Not Found');

        $httpResponse = Psr7ResponseAdapter::toHttpResponse($psr7Response);

        $this->assertEquals(404, $httpResponse->getResponseCode());
        $this->assertEquals('Resource Not Found', $httpResponse->getResponseCodeDescription());
    }

    public function testToHttpResponseHeaders(): void
    {
        $psr7Response = new Response(200);
        $psr7Response = $psr7Response->withHeader('X-Custom', 'value1');
        $psr7Response = $psr7Response->withHeader('X-Another', ['value2', 'value3']);

        $httpResponse = Psr7ResponseAdapter::toHttpResponse($psr7Response);

        $headers = $httpResponse->getHeaders();
        $this->assertArrayHasKey('X-Custom', $headers);
        $this->assertEquals(['value1'], $headers['X-Custom']);
        $this->assertArrayHasKey('X-Another', $headers);
    }

    public function testToHttpResponseJsonBody(): void
    {
        $data = ['id' => 456, 'name' => 'Testing'];
        $psr7Response = new Response(200);
        $psr7Response = $psr7Response->withHeader('Content-Type', 'application/json');
        $psr7Response = $psr7Response->withBody(
            new MemoryStream(json_encode($data))
        );

        $httpResponse = Psr7ResponseAdapter::toHttpResponse($psr7Response);

        $responseBag = $httpResponse->getResponseBag();
        $collection = $responseBag->getCollection();

        $this->assertNotEmpty($collection);
        $this->assertEquals($data, $collection[0]);
    }

    public function testToHttpResponseTextBody(): void
    {
        $psr7Response = new Response(200);
        $psr7Response = $psr7Response->withHeader('Content-Type', 'text/plain');
        $psr7Response = $psr7Response->withBody(
            new MemoryStream('Plain text response')
        );

        $httpResponse = Psr7ResponseAdapter::toHttpResponse($psr7Response);

        $responseBag = $httpResponse->getResponseBag();
        $collection = $responseBag->getCollection();

        $this->assertNotEmpty($collection);
        // When writing a string to ResponseBag, it wraps it in an array
        $this->assertEquals(['Plain text response'], $collection[0]);
    }

    public function testToHttpResponseExistingHttpResponse(): void
    {
        $existingResponse = new HttpResponse();
        $existingResponse->addHeader('X-Existing', 'existing-value');

        $psr7Response = new Response(201);
        $psr7Response = $psr7Response->withHeader('X-New', 'new-value');
        $psr7Response = $psr7Response->withBody(
            new MemoryStream('{"created": true}')
        );

        $httpResponse = Psr7ResponseAdapter::toHttpResponse($psr7Response, $existingResponse);

        $this->assertSame($existingResponse, $httpResponse);
        $this->assertEquals(201, $httpResponse->getResponseCode());

        $headers = $httpResponse->getHeaders();
        $this->assertArrayHasKey('X-New', $headers);
    }

    public function testRoundTripConversion(): void
    {
        // Create HttpResponse
        $originalResponse = new HttpResponse();
        $originalResponse->setResponseCode(200, 'OK');
        $originalResponse->addHeader('X-Test', 'test-value');
        $originalResponse->write(['message' => 'Hello World']);

        // Convert to PSR-7
        $psr7Response = Psr7ResponseAdapter::fromHttpResponse($originalResponse);

        // Convert back to HttpResponse
        $convertedResponse = Psr7ResponseAdapter::toHttpResponse($psr7Response);

        // Verify round-trip
        $this->assertEquals(200, $convertedResponse->getResponseCode());
        $this->assertEquals('OK', $convertedResponse->getResponseCodeDescription());

        $headers = $convertedResponse->getHeaders();
        $this->assertArrayHasKey('X-Test', $headers);

        $collection = $convertedResponse->getResponseBag()->getCollection();
        $this->assertEquals(['message' => 'Hello World'], $collection[0]);
    }

    public function testFromHttpResponseWithCustomContentType(): void
    {
        $httpResponse = new HttpResponse();
        $httpResponse->write('<xml><data>test</data></xml>');

        $psr7Response = Psr7ResponseAdapter::fromHttpResponse(
            $httpResponse,
            'application/xml'
        );

        $this->assertEquals('application/xml', $psr7Response->getHeaderLine('Content-Type'));
    }
}
