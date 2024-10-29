<?php

namespace ByJG\RestServer;

class HttpResponse
{

    /**
     * @var ResponseBag
     */
    protected ResponseBag $response;

    /**
     * @var ResponseBag|null
     */
    protected ?ResponseBag $responseDebug = null;

    /**
     * @var array
     */
    protected array $headers = [];

    /**
     * @var int
     */
    protected int $responseCode = 200;
    protected string $responseCodeDescription = 'OK';

    public function __construct()
    {
        $this->emptyResponse();
    }

    /**
     * Add a value in session
     *
     * @param string $name
     * @param string $value
     */
    public function setSession(string $name, string $value): void
    {
        $_SESSION[$name] = $value;
    }

    /**
     * Remove a value in this session
     *
     * @param string $name
     */
    public function removeSession(string $name): void
    {
        unset($_SESSION[$name]);
    }

    /**
     * Add a cookie value
     *
     * @param string $name
     * @param string $value
     * @param int|null $expire (seconds from now)
     * @param string|null $path (directory into domain in which the cookie will be available on )
     * @param string|null $domain
     */
    public function addCookie(string $name, string $value, int $expire = null, string $path = null, string $domain = null): void
    {
        if (!is_null($expire)) {
            $expire = time() + $expire;
        }
        setcookie($name, $value, $expire, $path, $domain);
    }

    /**
     * Delete a cookie
     *
     * @param string $name
     */
    public function removeCookie(string $name): void
    {
        setcookie($name, "", time() - 3600);
        unset($_COOKIE[$name]);
    }

    /**
     * ResponseBag is a collection of objects will be returned to the  client. RestServer call handle the ResponseBag to
     * return the proper output. Avoid to use it directly here. Prefer the methods write or writeDebug;
     *
     * @return ResponseBag
     */
    public function getResponseBag(): ResponseBag
    {
        return $this->response;
    }

    /**
     * Add an array, model or stdClass to be processed.
     *
     * @param mixed $object
     */
    public function write(mixed $object): void
    {
        $this->response->add($object);
    }

    /**
     * Added information for debug purposes only.
     * In case the error it will showed and the result a node called "debug" will be added.
     *
     * @param string $key
     * @param mixed $string
     */
    public function writeDebug(string $key, mixed $string): void
    {
        // @todo Review this.
        if (is_null($this->responseDebug)) {
            $this->responseDebug = new ResponseBag();
            $this->response->add($this->responseDebug);
        }
        $this->responseDebug->add(['debug' => [$key => $string]]);
        // ErrorHandler::getInstance()->addExtraInfo($key, serialize($string));
    }

    public function emptyResponse(): void
    {
        $this->response = new ResponseBag();
    }

    /**
     * Undocumented function
     *
     * @param string $header
     * @param array|string $value
     * @return void
     */
    public function addHeader(string $header, array|string $value): void
    {
        $this->headers[$header] = $value;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setResponseCode(int $code, string $description = ""): void
    {
        $this->responseCode = $code;
        $this->responseCodeDescription = $description;
    }

    public function getResponseCode(): int
    {
        return $this->responseCode;
    }

    public function getResponseCodeDescription(): string
    {
        return $this->responseCodeDescription;
    }
}
