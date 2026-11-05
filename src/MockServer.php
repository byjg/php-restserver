<?php

namespace ByJG\RestServer;

use ByJG\RestServer\Exception\Error404Exception;
use ByJG\RestServer\Exception\Error405Exception;
use ByJG\RestServer\Exception\Error422Exception;
use ByJG\RestServer\Exception\Error520Exception;
use ByJG\RestServer\Exception\OperationIdInvalidException;
use ByJG\RestServer\Route\RouteListInterface;
use ByJG\RestServer\Writer\MemoryWriter;
use ByJG\WebRequest\Exception\MessageException;
use ByJG\WebRequest\Psr7\MemoryStream;
use ByJG\WebRequest\Psr7\Response;
use ByJG\WebRequest\Psr7\ServerRequest;
use ByJG\Util\Uri;
use Override;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * Handles a PSR-7 request as a web server would, for tests: the response is kept in memory (see
 * getPsr7Response()), and while the request is handled PHP's globals ($_SERVER, $_GET, $_POST,
 * $_REQUEST, $_COOKIE, $_FILES) describe it, then they are restored.
 */
class MockServer extends Server
{
    /**
     * MockRequestHandler constructor.
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct(?LoggerInterface $logger = null)
    {
        $this->writer = new MemoryWriter();
        ErrorHandler::getInstance()->setLogger($logger ?? new NullLogger());
    }

    /**
     * Unlike Server::withRequestObject(), any PSR-7 request is accepted: a client request (as
     * php-swagger-test sends) is handled as if it reached a server, with made-up server params.
     *
     * @throws MessageException
     */
    #[Override]
    public function withRequestObject(RequestInterface $request): static
    {
        if (!$request instanceof ServerRequestInterface) {
            $request = self::toServerRequest($request);
        }
        parent::withRequestObject($request);
        return $this;
    }

    /**
     * @throws MessageException
     */
    private static function toServerRequest(RequestInterface $request): ServerRequest
    {
        $uri = $request->getUri();
        parse_str(preg_replace('/;\s*/', '&', $request->getHeaderLine('Cookie')) ?? '', $cookies);

        // The method, URI, query, headers and body come from the request (Psr7RequestAdapter). The URI is parsed
        // again from its string: a query given in the path (withPath('/list?page=2'), as php-swagger-test tests
        // do) becomes the query
        return (new ServerRequest(Uri::getInstance($uri), [
            'REMOTE_ADDR' => '127.0.0.1',
            'SERVER_SOFTWARE' => 'Mock',
            'SERVER_PROTOCOL' => 'HTTP/' . $request->getProtocolVersion(),
            'SERVER_NAME' => $uri->getHost(),
            // A web server always sets it; a URI leaves out the standard port of its scheme
            'SERVER_PORT' => $uri->getPort() ?? ($uri->getScheme() === 'https' ? 443 : 80),
            'SCRIPT_NAME' => $uri->getPath(),
            'PHP_SELF' => $uri->getPath(),
            'REQUEST_TIME' => time(),
            'REQUEST_TIME_FLOAT' => microtime(true),
        ], $cookies))
            ->withMethod($request->getMethod())
            ->withBody($request->getBody())
            ->withHeaders($request->getHeaders());
    }


    /**
     * @param RouteListInterface $routes
     * @param RequestInterface $request
     * @return MockServer
     * @throws Error404Exception
     * @throws Error405Exception
     * @throws Error520Exception
     * @throws Error422Exception
     * @throws OperationIdInvalidException
     */
    public static function mock(RouteListInterface $routes, RequestInterface $request)
    {
        $handler = new MockServer();
        $handler->withRequestObject($request);
        $handler->handle($routes);
        return $handler;
    }

    /**
     * Unlike Server, never built from the globals: the mock must be given its request.
     */
    #[Override]
    protected function getHttpRequest(): HttpRequest
    {
        return $this->httpRequest
            ?? throw new RuntimeException("MockServer::withRequestObject() must be called before handle method");
    }

    protected Response|null $psr7Response = null;

    /**
     * @throws MessageException
     */
    public function getPsr7Response(): ResponseInterface
    {
        if (is_null($this->psr7Response)) {
            /** @psalm-suppress UndefinedInterfaceMethod Always using MemoryWriter */
            $this->psr7Response = new Response($this->writer->getStatusCode());

            /** @psalm-suppress UndefinedInterfaceMethod Always using MemoryWriter */
            foreach ($this->writer->getHeaders() as $header => $value) {
                $this->psr7Response = $this->psr7Response->withHeader($header, $value);
            }

            /** @psalm-suppress UndefinedInterfaceMethod Always using MemoryWriter */
            $this->psr7Response = $this->psr7Response->withBody(new MemoryStream($this->writer->getData()));
        }


        return $this->psr7Response;
    }

    #[Override]
    public function handle(RouteListInterface $routeDefinition, bool $outputBuffer = true, bool $session = false): bool
    {
        $globals = [$_SERVER, $_GET, $_POST, $_REQUEST, $_COOKIE, $_FILES];
        $tempFiles = [];
        try {
            $tempFiles = $this->emulateGlobals($this->getHttpRequest());
            return parent::handle($routeDefinition, false);
        } finally {
            [$_SERVER, $_GET, $_POST, $_REQUEST, $_COOKIE, $_FILES] = $globals;
            array_map(static fn(string $file) => is_file($file) && unlink($file), $tempFiles);

            // Cleanup: unregister error handler after mock request processing completes
            // This prevents global state pollution and "risky test" warnings in PHPUnit
            // For MockRequestHandler, we always want to clean up since it's only used in testing
            ErrorHandler::getInstance()->unregister();
        }
    }

    /**
     * Fill PHP's globals from the request, as a web server would, for code that reads them. Each upload
     * is written to its own temporary file.
     *
     * @return string[] the temporary files
     */
    private function emulateGlobals(HttpRequest $request): array
    {
        $_SERVER = (array)$request->server();
        $_GET = (array)$request->query();
        $_POST = (array)$request->body();
        $_COOKIE = (array)$request->cookie();
        $_REQUEST = array_merge($_GET, $_POST);   // PHP's default request_order, "GP"

        $_FILES = [];
        $tempFiles = [];
        $uploads = $request->uploadedFiles();
        foreach ($uploads->getKeys() as $key) {
            $tmpName = tempnam(sys_get_temp_dir(), 'mock-upload-');
            if ($tmpName === false) {
                throw new RuntimeException("Cannot create a temporary file for the upload '$key'");
            }
            $tempFiles[] = $tmpName;
            file_put_contents($tmpName, (string)$uploads->getUploadedFile($key));
            $_FILES[$key] = [
                'name' => $uploads->getFileName($key),
                'type' => $uploads->getFileType($key),
                'tmp_name' => $tmpName,
                'error' => $uploads->getErrorCode($key),
                'size' => $uploads->getFileSize($key),
            ];
        }

        return $tempFiles;
    }
}
