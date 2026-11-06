<?php

namespace ByJG\RestServer\Middleware;

use ByJG\JwtWrapper\JwtWrapper;
use ByJG\JwtWrapper\JwtWrapperException;
use ByJG\RestServer\Exception\Error401Exception;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use Exception;
use Override;

class JwtMiddleware implements BeforeMiddlewareInterface
{
    const string JWT_PARAM_PREFIX = 'jwt';
    const string JWT_PARAM_PARSE_STATUS = 'jwt.parse.status';
    const string JWT_PARAM_PARSE_MESSAGE = 'jwt.parse.message';
    const string JWT_SUCCESS = 'success';
    const string JWT_FAILED = 'failed';

    protected array $ignorePath = [];
    protected JwtWrapper $jwtWrapper;

    public function __construct(JwtWrapper $jwtWrapper, array $ignorePath = [])
    {
        $this->jwtWrapper = $jwtWrapper;
        $this->ignorePath = $ignorePath;
    }

    /**
     * Undocumented function
     *
     * @param mixed $dispatcherStatus
     * @param HttpResponse $response
     * @param HttpRequest $request
     * @return MiddlewareResult
     * @throws Error401Exception
     */
    #[Override]
    public function beforeProcess(
        mixed        $dispatcherStatus,
        HttpResponse $response,
        HttpRequest  $request
    ): MiddlewareResult
    {
        foreach ($this->ignorePath as $path) {
            $requestPathStr = $request->getRequestPath() ?? '';
            if (preg_match("~$path~", $requestPathStr)) {
                return MiddlewareResult::Continue;
            }
        }

        $vars = [];
        try {
            // The token of this request. JwtWrapper::extractData() without one reads $_SERVER, which is not this
            // request's in a PSR-7 runtime or a persistent worker (Server::withRequestObject())
            $authorization = $request->getHeader('Authorization');
            $bearer = is_string($authorization) ? (string)(sscanf($authorization, 'Bearer %s')[0] ?? '') : '';
            if ($bearer === '') {
                throw new JwtWrapperException('Absent authorization token');
            }
            foreach ((array)$this->jwtWrapper->extractData($bearer) as $key => $value) {
                $vars[self::JWT_PARAM_PREFIX . '.' . $key] = $value;
            }
            $vars[self::JWT_PARAM_PARSE_STATUS] = self::JWT_SUCCESS;
        } catch (JwtWrapperException $ex) {
            $vars[self::JWT_PARAM_PARSE_STATUS] = self::JWT_FAILED;
            $vars[self::JWT_PARAM_PARSE_MESSAGE] = $ex->getMessage();
        } catch (Exception $ex) {
            throw new Error401Exception($ex->getMessage());
        }
        $request->addAttributes($vars);

        return MiddlewareResult::Continue;
    }
}
