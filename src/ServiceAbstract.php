<?php

namespace ByJG\RestServer;

class ServiceAbstract
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
}
