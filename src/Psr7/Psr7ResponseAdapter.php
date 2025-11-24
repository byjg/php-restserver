<?php

namespace ByJG\RestServer\Psr7;

use ByJG\RestServer\HttpResponse;
use ByJG\WebRequest\Exception\MessageException;
use ByJG\WebRequest\Psr7\MemoryStream;
use ByJG\WebRequest\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

/**
 * Adapter that converts HttpResponse to PSR-7 ResponseInterface
 *
 * This allows RestServer's HttpResponse to be converted to a PSR-7 compliant response
 * object for interoperability with PSR-7 middleware and frameworks.
 *
 * Note: PSR-7 responses are immutable and don't handle session/cookie operations
 * directly. Sessions are managed separately by the server, and cookies are set
 * via Set-Cookie headers.
 *
 * Example usage:
 * ```php
 * $httpResponse = new HttpResponse();
 * $httpResponse->write(['status' => 'success']);
 * $httpResponse->setResponseCode(200);
 * $httpResponse->addHeader('X-Custom-Header', 'value');
 *
 * $psr7Response = Psr7ResponseAdapter::fromHttpResponse($httpResponse);
 *
 * // Now you can use $psr7Response with any PSR-7 middleware
 * ```
 */
class Psr7ResponseAdapter
{
    /**
     * Convert HttpResponse to PSR-7 ResponseInterface
     *
     * @param HttpResponse $response
     * @param string $contentType Default content type if not set in headers
     * @return ResponseInterface
     * @throws MessageException
     */
    public static function fromHttpResponse(
        HttpResponse $response,
        string       $contentType = 'application/json'
    ): ResponseInterface
    {
        // Create PSR-7 Response with status code
        $psr7Response = new Response($response->getResponseCode());

        // Set reason phrase if custom
        $reasonPhrase = $response->getResponseCodeDescription();
        if (!empty($reasonPhrase)) {
            $psr7Response = $psr7Response->withStatus(
                $response->getResponseCode(),
                $reasonPhrase
            );
        }

        // Set headers
        $headers = $response->getHeaders();
        foreach ($headers as $name => $value) {
            $psr7Response = $psr7Response->withHeader($name, $value);
        }

        // Set content-type if not already set
        if (!$psr7Response->hasHeader('Content-Type')) {
            $psr7Response = $psr7Response->withHeader('Content-Type', $contentType);
        }

        // Convert ResponseBag to body
        $body = self::convertResponseBagToString($response, $contentType);
        $stream = new MemoryStream($body);
        return $psr7Response->withBody($stream);
    }

    /**
     * Convert HttpResponse back from PSR-7 ResponseInterface
     *
     * This is useful when you receive a PSR-7 response from middleware
     * and need to convert it back to HttpResponse.
     *
     * @param ResponseInterface $psr7Response
     * @param HttpResponse|null $httpResponse Existing response to update, or create new one
     * @return HttpResponse
     */
    public static function toHttpResponse(
        ResponseInterface $psr7Response,
        ?HttpResponse     $httpResponse = null
    ): HttpResponse
    {
        if ($httpResponse === null) {
            $httpResponse = new HttpResponse();
        }

        // Set status code
        $httpResponse->setResponseCode(
            $psr7Response->getStatusCode(),
            $psr7Response->getReasonPhrase()
        );

        // Set headers
        foreach ($psr7Response->getHeaders() as $name => $values) {
            $httpResponse->addHeader($name, $values);
        }

        // Set body
        $body = $psr7Response->getBody()->getContents();
        if (!empty($body)) {
            // Try to decode JSON if content-type is JSON
            $contentType = $psr7Response->getHeaderLine('Content-Type');
            if (str_contains($contentType, 'application/json')) {
                $decoded = json_decode($body, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $httpResponse->write($decoded);
                } else {
                    $httpResponse->write($body);
                }
            } else {
                $httpResponse->write($body);
            }
        }

        return $httpResponse;
    }

    /**
     * Convert ResponseBag content to string
     *
     * @param HttpResponse $response
     * @param string $contentType
     * @return string
     */
    private static function convertResponseBagToString(
        HttpResponse $response,
        string       $contentType
    ): string
    {
        $responseBag = $response->getResponseBag();
        $collection = $responseBag->getCollection();

        if (empty($collection)) {
            return '';
        }

        // If content type is JSON, encode the collection
        if (str_contains($contentType, 'application/json')) {
            // Flatten single-item arrays
            if (count($collection) === 1 && isset($collection[0])) {
                $data = $collection[0];
            } else {
                $data = $collection;
            }

            $encoded = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            return $encoded !== false ? $encoded : '{}';
        }

        // For other content types, convert to string
        if (count($collection) === 1) {
            $item = $collection[0];
            if (is_string($item)) {
                return $item;
            }
            if (is_scalar($item)) {
                return (string)$item;
            }
        }

        // Default: serialize as JSON
        $encoded = json_encode($collection, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return $encoded !== false ? $encoded : '[]';
    }
}
