<?php

namespace ByJG\RestServer;

abstract class ServiceAbstract
{

    /**
     * @var HttpRequest
     */
    protected $request;

    /**
     * @var HttpResponse
     */
    protected $response;

    public function __construct()
    {
        $this->request = new HttpRequest($_GET, $_POST, $_SERVER, $_SESSION, $_COOKIE);
        $this->response = new HttpResponse();
    }

    /**
     * @return \ByJG\RestServer\HttpRequest
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return \ByJG\RestServer\HttpResponse
     */
    public function getResponse()
    {
        return $this->response;
    }
}
