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
use ByJG\Util\Psr7\Message;
use ByJG\Util\Psr7\MessageException;
use ByJG\Util\Psr7\Response;
use ByJG\Util\Psr7\MemoryStream;
use Psr\Http\Message\MessageInterface;
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
     * @throws MessageException
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
     * @throws MessageException
     */
    public function handle(RouteDefinitionInterface $routeDefinition, $outputBuffer = true, $session = true)
    {
        return $this->callParentHandle($routeDefinition);
    }

    /**
     * @return HttpRequest|MockHttpRequest
     */
    protected function getHttpRequest()
    {
        return new MockHttpRequest($this->request);
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
     * @return Message|MessageInterface
     * @throws ClassNotFoundException
     * @throws Error404Exception
     * @throws Error405Exception
     * @throws Error520Exception
     * @throws InvalidClassException
     * @throws MessageException
     */
    protected function callParentHandle(RouteDefinitionInterface $routeDefinition)
    {
        $this->useErrorHandler = false;
        try {
            parent::handle($this->mockRoutes($routeDefinition), true, false);
        } finally {
            $content = ob_get_contents();
            ob_end_clean();
        }


        $rawResponse = explode("\r\n", $content);
        $statusMatch = [];
        preg_match("/HTTP\/\d\.\d (\d+)/", array_shift($rawResponse), $statusMatch);

        $response = new Response(intval($statusMatch[1]));

        while (!empty($line = array_shift($rawResponse))) {
            $parts = explode(":", $line);
            $response = $response->withHeader($parts[0], trim($parts[1]));
        }
        $response = $response->withBody(new MemoryStream(implode("\r\n", $rawResponse)));

        return $response;
    }

}
