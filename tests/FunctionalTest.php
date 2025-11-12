<?php

namespace Tests;

use ByJG\Util\Uri;
use ByJG\WebRequest\Exception\NetworkException;
use ByJG\WebRequest\HttpClient;
use ByJG\WebRequest\Psr7\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FunctionalTest extends TestCase
{
    #[DataProvider('dataProvider')]
    public function testHttpRequestJson(string $url, int $statusCode, string $contentType, string $contentBody): void
    {
        try {
            $uri = new Uri($url);
            $request = Request::getInstance($uri)
                ->withMethod('GET');
            $response = HttpClient::getInstance()->sendRequest($request);

            $this->assertEquals($statusCode, $response->getStatusCode());
            $this->assertEquals($contentType, $response->getHeaderLine('Content-Type'));
            $this->assertEquals($contentBody, $response->getBody()->getContents());
        } catch (NetworkException $ex) {
            $this->markTestSkipped($ex->getMessage());
        }
    }


    /**
     * @return list<array{string, int, string, string}>
     */
    public static function dataProvider(): array
    {
        return [
            ['http://localhost:8090/testjson', 200, 'application/json', '{"name":"It worked"}'],
            ['http://localhost:8090/testxml', 200, 'application/xml', '<?xml version="1.0"?>' . "\n" . '<root><name>It worked</name></root>' . "\n"],
            ['http://localhost:8090/testclosure', 200, 'application/json', '["OK"]'],
            ['http://localhost:8090/testoverride/xml-to-json', 200, 'application/json', '{"override":"xml-to-json"}'],
            ['http://localhost:8090/testoverride/json-to-xml', 200, 'application/xml', '<?xml version="1.0"?>' . "\n" . '<root><override>json-to-xml</override></root>' . "\n"],
            ['http://localhost:8090/testerror/400', 400, 'application/json', '{"error":{"type":"Error 400","message":"Teste"}}'],
            ['http://localhost:8090/testerror/401', 401, 'application/json', '{"error":{"type":"Error 401","message":"Teste"}}'],
            ['http://localhost:8090/testerror/402', 402, 'application/json', '{"error":{"type":"Error 402","message":"Teste"}}'],
            ['http://localhost:8090/testerror/403', 403, 'application/json', '{"error":{"type":"Error 403","message":"Teste"}}'],
            ['http://localhost:8090/testerror/404', 404, 'application/json', '{"error":{"type":"Error 404","message":"Teste"}}'],
            ['http://localhost:8090/testerror/405', 405, 'application/json', '{"error":{"type":"Error 405","message":"Teste"}}'],
            ['http://localhost:8090/testerror/406', 406, 'application/json', '{"error":{"type":"Error 406","message":"Teste"}}'],
            ['http://localhost:8090/testerror/408', 408, 'application/json', '{"error":{"type":"Error 408","message":"Teste"}}'],
            ['http://localhost:8090/testerror/409', 409, 'application/json', '{"error":{"type":"Error 409","message":"Teste"}}'],
            ['http://localhost:8090/testerror/412', 412, 'application/json', '{"error":{"type":"Error 412","message":"Teste"}}'],
            ['http://localhost:8090/testerror/415', 415, 'application/json', '{"error":{"type":"Error 415","message":"Teste"}}'],
            ['http://localhost:8090/testerror/422', 422, 'application/json', '{"error":{"type":"Error 422","message":"Teste"}}'],
            ['http://localhost:8090/testerror/429', 429, 'application/json', '{"error":{"type":"Error 429","message":"Teste"}}'],
            ['http://localhost:8090/testerror/500', 500, 'application/json', '{"error":{"type":"Error 500","message":"Teste"}}'],
            ['http://localhost:8090/testerror/501', 501, 'application/json', '{"error":{"type":"Error 501","message":"Teste"}}'],
            ['http://localhost:8090/testerror/501', 501, 'application/json', '{"error":{"type":"Error 501","message":"Teste"}}'],
            ['http://localhost:8090/testerror/503', 503, 'application/json', '{"error":{"type":"Error 503","message":"Teste"}}'],
            ['http://localhost:8090/testerror/520', 520, 'application/json', '{"error":{"type":"Error 520","message":"Teste"}}'],
        ];
    }
}