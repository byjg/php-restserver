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
            $requestPath = $request->getRequestPath();
            $requestPathStr = is_array($requestPath) ? '' : (string)$requestPath;
            if (preg_match("~$path~", $requestPathStr)) {
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
        } catch (Exception $ex) {
            throw new Error401Exception($ex->getMessage());
        }
        $request->appendVars($vars);

        return MiddlewareResult::continue;
    }
}
