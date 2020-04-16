<?php

namespace ByJG\RestServer;

use ByJG\Util\Psr7\Request;

class MockHttpRequest extends HttpRequest
{
    /**
     * @var Request
     */
    protected $psrRequest ;

    /**
     *
     * @param Request $psrRequest
     */
    public function __construct(Request $psrRequest)
    {
        $this->psrRequest = $psrRequest;

        $this->initializePhpVariables();

        parent::__construct($this->get, $this->post, $this->server, $this->session, $this->cookie);
    }

    private $payload;

    /**
     * Get the payload passed during the request(the same as php://input). If not found return empty.
     *
     * @return string
     */
    public function payload()
    {
        if (is_null($this->payload)) {
            $this->payload = $this->psrRequest->getBody()->getContents();
        }

        return $this->payload;
    }


    /**
     * Initilize PHP variables based on the request
     */
    protected function initializePhpVariables()
    {
        $this->session = [];

        $this->server = [];
        $this->server["REMOTE_ADDR"] = "127.0.0.1";
        $this->server["REMOTE_PORT"] = rand(1000, 60000);
        $this->server["SERVER_SOFTWARE"] = "Mock";
        $this->server["SERVER_PROTOCOL"] = "HTTP/" . $this->psrRequest->getProtocolVersion();
        $this->server["SERVER_NAME"] = $this->psrRequest->getUri()->getHost();
        $this->server["SERVER_PORT"] = $this->psrRequest->getUri()->getPort();
        $this->server["REQUEST_URI"] = $this->psrRequest->getRequestTarget();
        $this->server["REQUEST_METHOD"] = $this->psrRequest->getMethod();
        $this->server["SCRIPT_NAME"] = $this->psrRequest->getUri()->getPath();
        $this->server["SCRIPT_FILENAME"] = __FILE__;
        $this->server["PHP_SELF"] = $this->psrRequest->getUri()->getPath();
        $this->server["QUERY_STRING"] = $this->psrRequest->getUri()->getQuery();
        $this->server["HTTP_HOST"] = $this->psrRequest->getHeaderLine("Host");
        $this->server["HTTP_USER_AGENT"] = $this->psrRequest->getHeaderLine("User-Agent");

        // Headers and Cookies
        $this->cookie = [];
        foreach ($this->psrRequest->getHeaders() as $key => $value) {
            $this->server["HTTP_" . strtoupper($key)] = $this->psrRequest->getHeaderLine($key);

            if ($key == "Cookie") {
                parse_str(preg_replace("/;\s*/", "&", $this->psrRequest->getHeaderLine($key)), $this->cookie);
            }
        }

        $this->phpRequest = [];
        $this->get = [];
        $this->post = [];
        
        if (!empty($this->server["QUERY_STRING"])) {
            parse_str($this->server["QUERY_STRING"], $this->phpRequest);
            parse_str($this->server["QUERY_STRING"], $this->get);
        }

        if ($this->psrRequest->getHeaderLine("content-type") == "application/x-www-form-urlencoded") {
            parse_str($this->psrRequest->getBody()->getContents(), $this->post);
            parse_str($this->psrRequest->getBody()->getContents(), $post);
            array_merge($this->phpRequest, $post);
        }

        $this->initializePhpFileVar();
    }

    /**
     * Inicialize the PHP variable $_FILE
     */
    protected function initializePhpFileVar()
    {
        $_FILES = [];

        $contentType = $this->psrRequest->getHeaderLine("Content-Type");
        if (empty($contentType) || strpos($contentType, "multipart/") === false) {
            return;
        }

        $body = $this->psrRequest->getBody()->getContents();
        $matches = [];

        preg_match('/boundary=(.*)$/', $contentType, $matches);
        $boundary = $matches[1];

        // split content by boundary and get rid of last -- element
        $blocks = preg_split("/-+$boundary/", $body);
        array_pop($blocks);

        // loop data blocks
        foreach ($blocks as $id => $block) {
            if (empty($block))
                continue;

            if (strpos($block, 'application/octet-stream') !== false) {
                preg_match("/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s", $block, $matches);
            } else {
                preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches);
            }
            $_FILES[$matches[1]] = $matches[2];
        }
    }
}
