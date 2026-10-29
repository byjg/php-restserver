<?php

namespace ByJG\RestServer\Psr7;

use ByJG\RestServer\HttpRequest;
use ByJG\Util\Uri;
use ByJG\WebRequest\Exception\MessageException;
use ByJG\WebRequest\Exception\RequestException;
use ByJG\WebRequest\Psr7\MemoryStream;
use ByJG\WebRequest\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Adapter that converts HttpRequest to PSR-7 ServerRequestInterface, and back
 *
 * This allows RestServer's HttpRequest to be used as a PSR-7 compliant request
 * object for interoperability with PSR-7 middleware and frameworks, and a PSR-7
 * request to be handled by the Server (see Server::withRequestObject()).
 *
 * Example usage:
 * ```php
 * $httpRequest = new HttpRequest($_GET, $_POST, $_SERVER, $_SESSION, $_COOKIE);
 * $psr7Request = Psr7RequestAdapter::fromHttpRequest($httpRequest);
 *
 * // Now you can use $psr7Request with any PSR-7 middleware
 *
 * $httpRequest = Psr7RequestAdapter::toHttpRequest($psr7Request);
 * ```
 */
class Psr7RequestAdapter
{
    /**
     * Convert HttpRequest to PSR-7 ServerRequestInterface
     *
     * @param HttpRequest $request
     * @return ServerRequestInterface
     * @throws MessageException
     * @throws RequestException
     */
    public static function fromHttpRequest(HttpRequest $request): ServerRequestInterface
    {
        // Build URI from server parameters
        $serverParams = $request->server();
        if (!is_array($serverParams)) {
            $serverParams = [];
        }
        $uri = self::buildUriFromServer($serverParams);

        // Create PSR-7 ServerRequest
        $cookieParams = $request->cookie();
        if (!is_array($cookieParams)) {
            $cookieParams = [];
        }
        $psr7Request = new ServerRequest(
            $uri,
            $serverParams,
            $cookieParams
        );

        // Set HTTP method
        $method = $request->routeMethod() ?? 'GET';
        $psr7Request = $psr7Request->withMethod($method);

        // Set body from payload
        $payload = $request->payload();
        if (!empty($payload)) {
            $stream = new MemoryStream($payload);
            $psr7Request = $psr7Request->withBody($stream);
        }

        // Parse headers from server params
        foreach ($serverParams as $name => $value) {
            if (!is_string($name)) {
                continue;
            }
            if (str_starts_with($name, 'HTTP_')) {
                $headerName = str_replace('_', '-', substr($name, 5));
                if (is_string($headerName)) {
                    $psr7Request = $psr7Request->withHeader($headerName, $value);
                }
            } elseif (in_array($name, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'])) {
                $headerName = str_replace('_', '-', $name);
                $psr7Request = $psr7Request->withHeader($headerName, $value);
            }
        }

        // Set query params
        $getData = $request->query();
        if (is_array($getData) && !empty($getData)) {
            $psr7Request = $psr7Request->withQueryParams($getData);
        }

        // Set parsed body (POST data)
        // Note: withParsedBody also updates the body stream, so we need to set Content-Type first
        $postData = $request->body();
        if (is_array($postData) && !empty($postData)) {
            // Ensure Content-Type header is set for proper encoding
            if (!$psr7Request->hasHeader('Content-Type')) {
                $psr7Request = $psr7Request->withHeader('Content-Type', 'application/x-www-form-urlencoded');
            }
            $psr7Request = $psr7Request->withParsedBody($postData);
        }

        // Set request attributes as PSR-7 attributes
        $params = $request->attribute();
        if (!empty($params)) {
            foreach ($params as $key => $value) {
                $psr7Request = $psr7Request->withAttribute($key, $value);
            }
        }

        // Set route metadata as attributes
        $routeMetadata = $request->getRouteMetadata();
        if (!empty($routeMetadata)) {
            $psr7Request = $psr7Request->withAttribute('_route_metadata', $routeMetadata);
        }

        return $psr7Request;
    }

    /**
     * Convert a PSR-7 ServerRequestInterface to HttpRequest
     *
     * The request's method, URI and headers take precedence over its server params, so
     * $_SERVER-style keys (REQUEST_METHOD, REQUEST_URI, QUERY_STRING, HTTP_*) always
     * describe the PSR-7 request. The parsed body becomes the POST data only for form
     * content types, as PHP does; any body is available as the payload. The uploaded
     * files are the request's (see Psr7UploadedFiles), read only when used.
     *
     * Not converted: the session (PSR-7 has none).
     *
     * @param ServerRequestInterface $request
     * @return HttpRequest
     */
    public static function toHttpRequest(ServerRequestInterface $request): HttpRequest
    {
        $uri = $request->getUri();
        $query = $uri->getQuery() !== '' ? $uri->getQuery() : (string)($request->getServerParams()['QUERY_STRING'] ?? '');

        $server = $request->getServerParams();
        $server['REQUEST_METHOD'] = $request->getMethod();
        $server['REQUEST_URI'] = $uri->getPath() . ($query !== '' ? "?$query" : '');
        $server['QUERY_STRING'] = $query;
        foreach ($request->getHeaders() as $name => $values) {
            $key = strtoupper(str_replace('-', '_', (string)$name));
            $server[in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'], true) ? $key : "HTTP_$key"] = implode(', ', $values);
        }

        $post = [];
        $contentType = strtolower($request->getHeaderLine('Content-Type'));
        if (str_starts_with($contentType, 'application/x-www-form-urlencoded') || str_starts_with($contentType, 'multipart/form-data')) {
            $parsedBody = $request->getParsedBody();
            $post = is_array($parsedBody) ? $parsedBody : [];
        }
        // A stream's __toString() reads it from the start (PSR-7)
        $payload = (string)$request->getBody();

        $attributes = $request->getAttributes();
        $routeMetadata = $attributes['_route_metadata'] ?? [];
        unset($attributes['_route_metadata']);

        $httpRequest = new HttpRequest(
            $request->getQueryParams(),
            $post,
            $server,
            [],
            $request->getCookieParams(),
            $attributes,
            $payload
        );
        if (is_array($routeMetadata) && !empty($routeMetadata)) {
            $httpRequest->setRouteMetadata($routeMetadata);
        }
        $httpRequest->setUploadedFiles(new Psr7UploadedFiles($request));

        return $httpRequest;
    }

    /**
     * Build URI from server parameters
     *
     * @param array $server
     * @return Uri
     */
    private static function buildUriFromServer(array $server): Uri
    {
        // Determine scheme
        $scheme = 'http';
        if (isset($server['HTTPS']) && $server['HTTPS'] !== 'off') {
            $scheme = 'https';
        } elseif (isset($server['SERVER_PORT']) && $server['SERVER_PORT'] == 443) {
            $scheme = 'https';
        }

        // Determine host
        $host = '';
        if (isset($server['HTTP_HOST'])) {
            $host = $server['HTTP_HOST'];
        } elseif (isset($server['SERVER_NAME'])) {
            $host = $server['SERVER_NAME'];
        } elseif (isset($server['SERVER_ADDR'])) {
            $host = $server['SERVER_ADDR'];
        }

        // Extract port from host if present
        $port = null;
        if (str_contains($host, ':')) {
            $parts = explode(':', $host, 2);
            $host = $parts[0];
            $port = isset($parts[1]) ? (int)$parts[1] : null;
        } elseif (isset($server['SERVER_PORT'])) {
            $port = (int)$server['SERVER_PORT'];
        }

        // Don't include default ports
        if (($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443)) {
            $port = null;
        }

        // Determine path and query
        $path = '/';
        $query = '';
        if (isset($server['REQUEST_URI'])) {
            $requestUri = $server['REQUEST_URI'];
            if (str_contains($requestUri, '?')) {
                $parts = explode('?', $requestUri, 2);
                $path = $parts[0];
                $query = $parts[1] ?? '';
            } else {
                $path = $requestUri;
            }
        } elseif (isset($server['PHP_SELF'])) {
            $path = $server['PHP_SELF'];
        }

        if (empty($query) && isset($server['QUERY_STRING'])) {
            $query = $server['QUERY_STRING'];
        }

        // Build URI string
        $uriString = $scheme . '://' . $host;
        if ($port !== null) {
            $uriString .= ':' . $port;
        }
        $uriString .= $path;
        if (!empty($query)) {
            $uriString .= '?' . $query;
        }

        return new Uri($uriString);
    }
}
