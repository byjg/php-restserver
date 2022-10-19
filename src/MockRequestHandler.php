<?php

namespace ByJG\RestServer;

use ByJG\RestServer\Exception\ClassNotFoundException;
use ByJG\RestServer\Exception\Error404Exception;
use ByJG\RestServer\Exception\Error405Exception;
use ByJG\RestServer\Exception\Error520Exception;
use ByJG\RestServer\Exception\InvalidClassException;
use ByJG\RestServer\OutputProcessor\MockOutputProcessor;
use ByJG\RestServer\Route\RouteList;
use ByJG\RestServer\Route\RouteListInterface;
use ByJG\RestServer\Route\Route;
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
     * @param RouteListInterface $routes
     * @param RequestInterface $request
     * @return Response
     * @throws ClassNotFoundException
     * @throws Error404Exception
     * @throws Error405Exception
     * @throws Error520Exception
     * @throws InvalidClassException
     * @throws MessageException
     */
    public static function mock(RouteListInterface $routes, RequestInterface $request)
    {
        $handler = new MockRequestHandler($request);
        return $handler->handle($routes, true, true);
    }

    /**
     * @param RouteListInterface $routeDefinition
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
    public function handle(RouteListInterface $routeDefinition, $outputBuffer = true, $session = true)
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
     * @param RouteListInterface $routeDefinition
     * @return RouteList
     */
    protected function mockRoutes(RouteListInterface $routeDefinition)
    {
        // Redo Route Definition
        $mockRoutes = new RouteList();
        foreach ($routeDefinition->getRoutes() as $route) {
            $class = $route->getClass();
            $methodName = "";
            if (is_array($class)) {
                $methodName = $class[1];
                $class = $class[0];
            }
            $mockRoutes->addRoute(
                new Route(
                    $route->getMethod(),
                    $route->getPath(),
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
     * @param RouteListInterface $routeDefinition
     * @return Message|MessageInterface
     * @throws ClassNotFoundException
     * @throws Error404Exception
     * @throws Error405Exception
     * @throws Error520Exception
     * @throws InvalidClassException
     * @throws MessageException
     */
    protected function callParentHandle(RouteListInterface $routeDefinition)
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
