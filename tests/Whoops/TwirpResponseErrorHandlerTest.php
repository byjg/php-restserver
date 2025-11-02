<?php

namespace Tests\Whoops;

use ByJG\RestServer\Exception\Error400Exception;
use ByJG\RestServer\Exception\Error401Exception;
use ByJG\RestServer\Exception\Error403Exception;
use ByJG\RestServer\Exception\Error404Exception;
use ByJG\RestServer\Exception\Error408Exception;
use ByJG\RestServer\Exception\Error409Exception;
use ByJG\RestServer\Exception\Error412Exception;
use ByJG\RestServer\Exception\Error422Exception;
use ByJG\RestServer\Exception\Error429Exception;
use ByJG\RestServer\Exception\Error500Exception;
use ByJG\RestServer\Exception\Error501Exception;
use ByJG\RestServer\Exception\Error503Exception;
use ByJG\RestServer\Whoops\TwirpResponseErrorHandler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Whoops\Exception\Inspector;

class TwirpResponseErrorHandlerTest extends TestCase
{
    #[DataProvider('dataProvider')]
    public function testTwirpResponse(string $class, int $code, string $description): void
    {
        $handler = new TwirpResponseErrorHandler();
        ob_start();
        $ex = new $class("teste");
        $handler->setException($ex);
        $handler->setInspector(new Inspector($ex));
        $handler->handle();
        $result = ob_get_contents();
        ob_end_clean();
        $this->assertEquals('{"code":"' . $description . '","msg":"teste"}', $result);
    }

    /**
     * @return (int|string)[][]
     *
     * @psalm-return list{list{Error408Exception::class, 408, 'canceled'}, list{Error400Exception::class, 400, 'invalid_argument'}, list{Error422Exception::class, 422, 'invalid_argument'}, list{Error404Exception::class, 404, 'not_found'}, list{Error403Exception::class, 403, 'permission_denied'}, list{Error401Exception::class, 401, 'unauthenticated'}, list{Error429Exception::class, 429, 'resource_exhausted'}, list{Error412Exception::class, 412, 'failed_precondition'}, list{Error409Exception::class, 409, 'aborted'}, list{Error500Exception::class, 500, 'internal'}, list{Error501Exception::class, 501, 'unimplemented'}, list{Error503Exception::class, 503, 'unavailable'}}
     */
    public static function dataProvider(): array
    {
        return [
            [ Error408Exception::class, 408, "canceled" ],
            [ Error400Exception::class, 400, "invalid_argument" ],
            [ Error422Exception::class, 422, "invalid_argument" ],
            [ Error404Exception::class, 404, "not_found" ],
            [ Error403Exception::class, 403, "permission_denied" ],
            [ Error401Exception::class, 401, "unauthenticated" ],
            [ Error429Exception::class, 429, "resource_exhausted" ],
            [ Error412Exception::class, 412, "failed_precondition" ],
            [ Error409Exception::class, 409, "aborted" ],
            [ Error500Exception::class, 500, "internal" ],
            [ Error501Exception::class, 501, "unimplemented" ],
            [ Error503Exception::class, 503, "unavailable" ],
        ];
    }

    #[DataProvider('dataProvider')]
    public function testTwirpResponseMeta(string $class, int $code, string $description): void
    {
        $handler = new TwirpResponseErrorHandler();
        ob_start();
        if ($code !== 500) {
            $ex = new $class("teste", meta: ['test' => 'ok']);
            $meta = ',"meta":{"test":"ok"}';
        } else {
            $ex = new $class("teste");
            $meta = "";
        }
        $handler->setException($ex);
        $handler->setInspector(new Inspector($ex));
        $handler->handle();
        $result = ob_get_contents();
        ob_end_clean();
        $this->assertEquals('{"code":"' . $description . '","msg":"teste"' . $meta . '}', $result);
    }
}
