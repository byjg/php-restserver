<?php

namespace Tests;

use ByJG\JwtWrapper\JwtHashHmacSecret;
use ByJG\JwtWrapper\JwtWrapper;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\Middleware\JwtMiddleware;
use ByJG\RestServer\OutputProcessor\JsonOutputProcessor;
use ByJG\RestServer\Route\Route;
use ByJG\RestServer\Route\RouteList;
use ByJG\RestServer\Server;
use ByJG\RestServer\Writer\MemoryWriter;
use ByJG\Util\Uri;
use ByJG\WebRequest\Psr7\ServerRequest;
use Override;
use PHPUnit\Framework\TestCase;

/**
 * JwtMiddleware reads the token from the request it handles, not from PHP's globals: a PSR-7 request
 * (Server::withRequestObject(), a persistent worker) has its own, and the globals can hold another request's
 */
class JwtMiddlewareRequestTest extends TestCase
{
    private JwtWrapper $jwtWrapper;

    #[Override]
    public function setUp(): void
    {
        $this->jwtWrapper = new JwtWrapper("localhost", new JwtHashHmacSecret("password-that-is-long-enough-for-hs256", decode: false, algorithm: 'HS256'));
        unset($_SERVER['HTTP_AUTHORIZATION']);
    }

    #[Override]
    public function tearDown(): void
    {
        unset($_SERVER['HTTP_AUTHORIZATION']);
    }

    /**
     * @return array<string, mixed> the attributes the middleware set
     */
    private function attributes(array $server): array
    {
        $request = new HttpRequest([], [], $server + ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/item'], [], [], [], '');
        (new JwtMiddleware($this->jwtWrapper))->beforeProcess(null, new HttpResponse(), $request);
        return $request->attribute();
    }

    public function testTheTokenOfTheRequest(): void
    {
        $token = $this->jwtWrapper->generateToken(['userid' => 'from-request']);

        $attributes = $this->attributes(['HTTP_AUTHORIZATION' => "Bearer $token"]);

        $this->assertSame(JwtMiddleware::JWT_SUCCESS, $attributes[JwtMiddleware::JWT_PARAM_PARSE_STATUS]);
        $this->assertSame('from-request', $attributes['jwt.userid']);
    }

    public function testNotTheTokenOfTheGlobals(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $this->jwtWrapper->generateToken(['userid' => 'from-globals']);
        $token = $this->jwtWrapper->generateToken(['userid' => 'from-request']);

        $this->assertSame('from-request', $this->attributes(['HTTP_AUTHORIZATION' => "Bearer $token"])['jwt.userid']);
    }

    /**
     * A request without a token is not authenticated, whatever token the globals have (the previous request's, in a
     * worker that fills them)
     */
    public function testARequestWithoutATokenIsNotAuthenticatedByTheGlobals(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $this->jwtWrapper->generateToken(['userid' => 'from-globals']);

        $attributes = $this->attributes([]);

        $this->assertSame(JwtMiddleware::JWT_FAILED, $attributes[JwtMiddleware::JWT_PARAM_PARSE_STATUS]);
        $this->assertSame('Absent authorization token', $attributes[JwtMiddleware::JWT_PARAM_PARSE_MESSAGE]);
        $this->assertArrayNotHasKey('jwt.userid', $attributes);
    }

    /**
     * A Server handling a PSR-7 request: the token is in the request only
     */
    public function testAServerHandlingAPsr7Request(): void
    {
        $token = $this->jwtWrapper->generateToken(['userid' => '123']);
        $writer = new MemoryWriter();
        $routes = (new RouteList())->addRoute(Route::get('/item')->withOutputProcessor(JsonOutputProcessor::class)->withClosure(
            function (HttpResponse $response, HttpRequest $request) {
                $response->write(['status' => $request->attribute(JwtMiddleware::JWT_PARAM_PARSE_STATUS), 'userid' => $request->attribute('jwt.userid')]);
            }
        ));

        (new Server())->withErrorHandlerDisabled()->withWriter($writer)->withMiddleware(new JwtMiddleware($this->jwtWrapper))
            ->withRequestObject((new ServerRequest(Uri::getInstance('http://localhost/item')))->withMethod('GET')->withHeader('Authorization', "Bearer $token"))
            ->handle($routes, false);

        $this->assertSame('{"status":"success","userid":"123"}', $writer->getData());
    }
}
