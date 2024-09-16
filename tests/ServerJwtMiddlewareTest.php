<?php

namespace Tests;

use ByJG\JwtWrapper\JwtHashHmacSecret;
use ByJG\JwtWrapper\JwtWrapper;
use ByJG\RestServer\Exception\Error401Exception;
use ByJG\RestServer\Middleware\JwtMiddleware;
use PHPUnit\Framework\TestCase;

class ServerJwtMiddlewareTest extends TestCase
{
    use MockServerTrait;

    public function testJwtMiddlewareWithToken(): void
    {
        $jwtKey = new JwtHashHmacSecret("password", decode: false);
        $jwtWrapper = new JwtWrapper("localhost", $jwtKey);
        $token = $jwtWrapper->generateToken(["userid" => "123"]);

        $expectedHeader = [
            "HTTP/1.1 200 OK",
            "Content-Type: application/json",
        ];
        $expectedData = '{"jwt.parse.status":"success","jwt.parse.message":false,"jwt.userid":"123"}';

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "http://localhost/testjwt";
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer $token";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;


        $this->processAndGetContent($this->object, $expectedHeader, $expectedData, new JwtMiddleware($jwtWrapper));
    }

    public function testJwtMiddlewareEmptyToken(): void
    {
        $jwtKey = new JwtHashHmacSecret("password", decode: false);
        $jwtWrapper = new JwtWrapper("localhost", $jwtKey);

        $expectedHeader = [
            "HTTP/1.1 200 OK",
            "Content-Type: application/json",
        ];
        $expectedData = '{"jwt.parse.status":"failed","jwt.parse.message":"Absent authorization token","jwt.userid":false}';

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "http://localhost/testjwt";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;


        $this->processAndGetContent($this->object, $expectedHeader, $expectedData, new JwtMiddleware($jwtWrapper));
    }


    public function testJwtMiddlewareWrongToken(): void
    {
        $this->expectException(Error401Exception::class);

        $jwtKey = new JwtHashHmacSecret("wrong", decode: false);
        $jwtWrapper = new JwtWrapper("other", $jwtKey);
        $token = $jwtWrapper->generateToken(["userid" => "150"]);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "http://localhost/testjwt";
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer $token";
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;

        $jwtKey2 = new JwtHashHmacSecret("password", decode: false);
        $jwtWrapper2 = new JwtWrapper("localhost", $jwtKey2);

        $this->processAndGetContent($this->object, null, '[]', new JwtMiddleware($jwtWrapper2));
    }
}
