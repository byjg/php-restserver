<?php

namespace ByJG\RestServer;

use ByJG\RestServer\Exception\ClassNotFoundException;
use ByJG\RestServer\Exception\Error404Exception;
use ByJG\RestServer\Exception\Error405Exception;
use ByJG\RestServer\Exception\Error520Exception;
use ByJG\RestServer\Exception\InvalidClassException;
use ByJG\RestServer\OutputProcessor\MockOutputProcessor;
use ByJG\RestServer\Route\RouteDefinition;
use ByJG\RestServer\Route\RouteDefinitionInterface;
use ByJG\RestServer\Route\RoutePattern;
use ByJG\Util\Psr7\Response;
use MintWare\Streams\MemoryStream;
use Psr\Http\Message\RequestInterface;

class MockRequestHandler extends HttpRequestHandler
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * MockRequestHandler constructor.
     * @param RequestInterface $request
     */
    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }


    /**
     * @param RouteDefinitionInterface $routes
     * @param RequestInterface $request
     * @return Response
     * @throws ClassNotFoundException
     * @throws Error404Exception
     * @throws Error405Exception
     * @throws Error520Exception
     * @throws InvalidClassException
     * @throws \ByJG\Util\Psr7\MessageException
     */
    public static function mock(RouteDefinitionInterface $routes, RequestInterface $request)
    {
        $handler = new MockRequestHandler($request);
        return $handler->handle($routes, true, true);
    }

    /**
     * @param RouteDefinitionInterface $routeDefinition
     * @param bool $outputBuffer
     * @param bool $session
     * @return bool|Response|void
     * @throws ClassNotFoundException
     * @throws Error404Exception
     * @throws Error405Exception
     * @throws Error520Exception
     * @throws InvalidClassException
     * @throws \ByJG\Util\Psr7\MessageException
     */
    public function handle(RouteDefinitionInterface $routeDefinition, $outputBuffer = true, $session = true)
    {
        $this->initializePhpVariables();

        return $this->callParentHandle($routeDefinition);
    }

    /**
     * Initilize PHP variables based on the request
     */
    protected function initializePhpVariables()
    {
        $_SESSION = null;

        $_SERVER = [];
        $_SERVER["REMOTE_ADDR"] = "127.0.0.1";
        $_SERVER["REMOTE_PORT"] = rand(1000, 60000);
        $_SERVER["SERVER_SOFTWARE"] = "Mock";
        $_SERVER["SERVER_PROTOCOL"] = "HTTP/" . $this->request->getProtocolVersion();
        $_SERVER["SERVER_NAME"] = $this->request->getUri()->getHost();
        $_SERVER["SERVER_PORT"] = $this->request->getUri()->getPort();
        $_SERVER["REQUEST_URI"] = $this->request->getRequestTarget();
        $_SERVER["REQUEST_METHOD"] = $this->request->getMethod();
        $_SERVER["SCRIPT_NAME"] = $this->request->getUri()->getPath();
        $_SERVER["SCRIPT_FILENAME"] = __FILE__;
        $_SERVER["PHP_SELF"] = $this->request->getUri()->getPath();
        $_SERVER["QUERY_STRING"] = $this->request->getUri()->getQuery();
        $_SERVER["HTTP_HOST"] = $this->request->getHeaderLine("Host");
        $_SERVER["HTTP_USER_AGENT"] = $this->request->getHeaderLine("User-Agent");

        // Headers and Cookies
        $_COOKIE = [];
        foreach ($this->request->getHeaders() as $key => $value) {
            $_SERVER["HTTP_" . strtoupper($key)] = $this->request->getHeaderLine($key);

            if ($key == "Cookie") {
                parse_str(preg_replace("/;\s*/", "&", $this->request->getHeaderLine($key)), $_COOKIE);
            }
        }

        $_REQUEST = [];
        if (!empty($_SERVER["QUERY_STRING"])) {
            parse_str($_SERVER["QUERY_STRING"], $_REQUEST);
        }

        $_POST = [];
        if ($this->request->getHeaderLine("content-type") == "application/x-www-form-urlencoded") {
            parse_str($this->request->getBody(), $_POST);
        }

        $this->initializePhpFileVar();
    }

    /**
     * Inicialize the PHP variable $_FILE
     */
    protected function initializePhpFileVar()
    {
        $_FILES = [];

        $contentType = $this->request->getHeaderLine("Content-Type");
        if (empty($contentType) || strpos($contentType, "multipart/") === false) {
            return;
        }

        $body = $this->request->getBody()->getContents();
        $matches = [];

        preg_match('/boundary=(.*)$/', $contentType, $matches);
        $boundary = $matches[1];

        // split content by boundary and get rid of last -- element
        $blocks = preg_split("/-+$boundary/", $body);
        array_pop($blocks);

        // loop data blocks
        foreach ($blocks as $id => $block) {
            if (empty($block))
                continue;

            if (strpos($block, 'application/octet-stream') !== false) {
                preg_match("/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s", $block, $matches);
            } else {
                preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches);
            }
            $_FILES[$matches[1]] = $matches[2];
        }
    }

    /**
     * @param RouteDefinitionInterface $routeDefinition
     * @return RouteDefinition
     */
    protected function mockRoutes(RouteDefinitionInterface $routeDefinition)
    {
        // Redo Route Definition
        $mockRoutes = new RouteDefinition();
        foreach ($routeDefinition->getRoutes() as $route) {
            $class = $route->getClass();
            $methodName = "";
            if (is_array($class)) {
                $methodName = $class[1];
                $class = $class[0];
            }
            $mockRoutes->addRoute(
                new RoutePattern(
                    $route->getMethod(),
                    $route->getPattern(),
                    function () use ($route) {
                        return new MockOutputProcessor($route->getOutputProcessor());
                    },
                    $class,
                    $methodName
                )
            );
        }

        return $mockRoutes;
    }

    /**
     * @param RouteDefinitionInterface $routeDefinition
     * @return Response
     * @throws ClassNotFoundException
     * @throws Error404Exception
     * @throws Error405Exception
     * @throws Error520Exception
     * @throws InvalidClassException
     * @throws \ByJG\Util\Psr7\MessageException
     */
    protected function callParentHandle(RouteDefinitionInterface $routeDefinition)
    {
        $this->useErrorHandler = false;
        parent::handle($this->mockRoutes($routeDefinition), true, false);
        $content = ob_get_contents();
        ob_end_clean();


        $rawResponse = explode("\r\n", $content);
        $statusMatch = [];
        preg_match("/HTTP\/\d\.\d (\d+)/", array_shift($rawResponse), $statusMatch);

        $response = new Response(intval($statusMatch[1]));

        while (!empty($line = array_shift($rawResponse))) {
            $parts = explode(":", $line);
            $response->withHeader($parts[0], trim($parts[1]));
        }
        $response->withBody(new MemoryStream(implode("\r\n", $rawResponse)));

        return $response;
    }

}
