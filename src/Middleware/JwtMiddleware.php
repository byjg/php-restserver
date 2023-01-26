<?php

namespace ByJG\RestServer\Middleware;

use ByJG\RestServer\Exception\Error401Exception;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\ResponseBag;
use ByJG\Util\JwtWrapper;
use ByJG\Util\JwtWrapperException;
use Exception;

class JwtMiddleware implements BeforeMiddlewareInterface
{

    protected $ignorePath = [];
    protected $jwtWrapper;

    public function __construct(JwtWrapper $jwtWrapper, $ignorePath = [])
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
     */
    public function beforeProcess(
        $dispatcherStatus,
        HttpResponse $response,
        HttpRequest $request
    )
    {
        foreach ($this->ignorePath as $path) {
            if (preg_match("~$path~", $request->getRequestPath())) {
                return MiddlewareResult::continue();
            }
        }

        try {
            $request->appendVars($this->jwtWrapper->extractData());
        } catch (JwtWrapperException $ex) {
            throw new Error401Exception($ex->getMessage());
        }

        return MiddlewareResult::continue();
    }
}
