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
 * Adapter that converts HttpRequest to PSR-7 ServerRequestInterface
 *
 * This allows RestServer's HttpRequest to be used as a PSR-7 compliant request
 * object for interoperability with PSR-7 middleware and frameworks.
 *
 * Example usage:
 * ```php
 * $httpRequest = new HttpRequest($_GET, $_POST, $_SERVER, $_SESSION, $_COOKIE);
 * $psr7Request = Psr7RequestAdapter::fromHttpRequest($httpRequest);
 *
 * // Now you can use $psr7Request with any PSR-7 middleware
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
        $uri = self::buildUriFromServer($serverParams);

        // Create PSR-7 ServerRequest
        $psr7Request = new ServerRequest(
            $uri,
            $serverParams,
            $request->cookie() ?? []
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
            if (str_starts_with($name, 'HTTP_')) {
                $headerName = str_replace('_', '-', substr($name, 5));
                $psr7Request = $psr7Request->withHeader($headerName, $value);
            } elseif (in_array($name, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'])) {
                $headerName = str_replace('_', '-', $name);
                $psr7Request = $psr7Request->withHeader($headerName, $value);
            }
        }

        // Set query params
        $getData = $request->get();
        if (!empty($getData)) {
            $psr7Request = $psr7Request->withQueryParams($getData);
        }

        // Set parsed body (POST data)
        // Note: withParsedBody also updates the body stream, so we need to set Content-Type first
        $postData = $request->post();
        if (!empty($postData)) {
            // Ensure Content-Type header is set for proper encoding
            if (!$psr7Request->hasHeader('Content-Type')) {
                $psr7Request = $psr7Request->withHeader('Content-Type', 'application/x-www-form-urlencoded');
            }
            $psr7Request = $psr7Request->withParsedBody($postData);
        }

        // Set route params as attributes
        $params = $request->param();
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
            list($host, $port) = explode(':', $host, 2);
            $port = (int)$port;
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
                list($path, $query) = explode('?', $requestUri, 2);
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
