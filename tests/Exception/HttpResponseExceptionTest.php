<?php

namespace Tests\Exception;

use ByJG\RestServer\Exception\Error400Exception;
use ByJG\RestServer\Exception\Error401Exception;
use ByJG\RestServer\Exception\Error402Exception;
use ByJG\RestServer\Exception\Error403Exception;
use ByJG\RestServer\Exception\Error404Exception;
use ByJG\RestServer\Exception\Error405Exception;
use ByJG\RestServer\Exception\Error406Exception;
use ByJG\RestServer\Exception\Error408Exception;
use ByJG\RestServer\Exception\Error409Exception;
use ByJG\RestServer\Exception\Error412Exception;
use ByJG\RestServer\Exception\Error415Exception;
use ByJG\RestServer\Exception\Error422Exception;
use ByJG\RestServer\Exception\Error429Exception;
use ByJG\RestServer\Exception\Error501Exception;
use ByJG\RestServer\Exception\Error503Exception;
use ByJG\RestServer\Exception\Error520Exception;
use ByJG\RestServer\Exception\HttpResponseException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HttpResponseExceptionTest extends TestCase
{
    #[DataProvider('dataProvider')]
    public function testConstructor(string $class, int $code, string $description): void
    {
        /** @var HttpResponseException $ex */
        $ex = new $class();
        $this->assertEquals($code, $ex->getStatusCode());
        $this->assertEquals($description, $ex->getStatusMessage());
    }

    /**
     * @return (int|string)[][]
     *
     * @psalm-return list{list{Error400Exception::class, 400, 'Bad Request'}, list{Error401Exception::class, 401, 'Unauthorized'}, list{Error402Exception::class, 402, 'Payment Required'}, list{Error403Exception::class, 403, 'Forbidden'}, list{Error404Exception::class, 404, 'Not Found'}, list{Error405Exception::class, 405, 'Method not allowed'}, list{Error406Exception::class, 406, 'Not Acceptable'}, list{Error408Exception::class, 408, 'Request Timeout'}, list{Error409Exception::class, 409, 'Conflict'}, list{Error412Exception::class, 412, 'Precondition Failed'}, list{Error415Exception::class, 415, 'Unsupported Media Type'}, list{Error422Exception::class, 422, 'Unprocessable Entity'}, list{Error429Exception::class, 429, 'Too many requests'}, list{Error501Exception::class, 501, 'Not Implemented'}, list{Error503Exception::class, 503, 'Service Unavailable'}, list{Error520Exception::class, 520, 'Unknown Error'}}
     */
    public static function dataProvider(): array
    {
        return [
            [ Error400Exception::class, 400, 'Bad Request' ],
            [ Error401Exception::class, 401, 'Unauthorized' ],
            [ Error402Exception::class, 402, 'Payment Required' ],
            [ Error403Exception::class, 403, 'Forbidden' ],
            [ Error404Exception::class, 404, 'Not Found' ],
            [ Error405Exception::class, 405, 'Method not allowed' ],
            [ Error406Exception::class, 406, 'Not Acceptable' ],
            [ Error408Exception::class, 408, 'Request Timeout' ],
            [ Error409Exception::class, 409, 'Conflict' ],
            [ Error412Exception::class, 412, 'Precondition Failed' ],
            [ Error415Exception::class, 415, 'Unsupported Media Type' ],
            [ Error422Exception::class, 422, 'Unprocessable Entity' ],
            [ Error429Exception::class, 429, 'Too many requests' ],
            [ Error501Exception::class, 501, 'Not Implemented' ],
            [ Error503Exception::class, 503, 'Service Unavailable' ],
            [ Error520Exception::class, 520, 'Unknown Error' ],
        ];
    }
}
