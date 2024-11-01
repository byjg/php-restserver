<?php

namespace ByJG\RestServer\Middleware;

use ByJG\JwtWrapper\JwtWrapper;
use ByJG\JwtWrapper\JwtWrapperException;
use ByJG\RestServer\Exception\Error401Exception;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;

class JwtMiddleware implements BeforeMiddlewareInterface
{
    const JWT_PARAM_PREFIX = 'jwt';
    const JWT_PARAM_PARSE_STATUS = 'jwt.parse.status';
    const JWT_PARAM_PARSE_MESSAGE = 'jwt.parse.message';
    const JWT_SUCCESS = 'success';
    const JWT_FAILED = 'failed';

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
    public function beforeProcess(
        mixed        $dispatcherStatus,
        HttpResponse $response,
        HttpRequest  $request
    ): MiddlewareResult
    {
        foreach ($this->ignorePath as $path) {
            if (preg_match("~$path~", $request->getRequestPath())) {
                return MiddlewareResult::continue;
            }
        }

        $vars = [];
        try {
            foreach ((array)$this->jwtWrapper->extractData() as $key => $value) {
                $vars[self::JWT_PARAM_PREFIX . '.' . $key] = $value;
            }
            $vars[self::JWT_PARAM_PARSE_STATUS] = self::JWT_SUCCESS;
        } catch (JwtWrapperException $ex) {
            $vars[self::JWT_PARAM_PARSE_STATUS] = self::JWT_FAILED;
            $vars[self::JWT_PARAM_PARSE_MESSAGE] = $ex->getMessage();
        } catch (\Exception $ex) {
            throw new Error401Exception($ex->getMessage());
        }
        $request->appendVars($vars);

        return MiddlewareResult::continue;
    }
}
