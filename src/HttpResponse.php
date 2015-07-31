<?php

namespace ByJG\RestServer;

class HttpResponse
{
    /**
     * @var ResponseBag
     */
    protected $response;

    /**
     * @var ResponseBag
     */
    protected $responseDebug;

    public function __construct()
    {
        $this->response = new ResponseBag();
    }

	/**
     * Add a value in session
     *
     * @param string $name
     * @param string $value
     */
	public function setSession($name, $value)
	{
		$_SESSION[$name] = $value;
	}

	/**
     * Remove a value in this session
     *
     * @param string $name
	 */
	public function removeSession($name)
	{
		unset($_SESSION[$name]);
	}

	/**
     * Add a cookie value
     *
     * @param string $name
     * @param string $value
     * @param int $expire (seconds from now)
     * @param int $path (directory into domain in which the cookie will be available on )
     */
	public function addCookie($name, $value, $expire = null, $path = null, $domain = null)
	{
		if (!is_null($expire))
		{
			$expire = time() + $expire;
		}
		setcookie($name, $value, $expire, $path, $domain);
	}

	/**
     * Delete a cookie
     *
     * @param string $name
     */
    public function removeCookie($name)
	{
		setcookie($name, null, time() - 3600);
		unset($_COOKIE[$name]);
	}

    /**
     * ResponseBag is a collection of objects will be returned to the  client. RestServer call handle the ResponseBag to
     * return the proper output. Avoid to use it directly here. Prefer the methods write or writeDebug;
     *
     * @return ResponseBag
     */
    public function getResponseBag()
    {
        return $this->response;
    }

    /**
     * Add an array, model or stdClass to be processed.
     *
     * @param mixed $object
     */
    public function write($object)
    {
        $this->response->add($object);
    }

    /**
     * Added informations for debug purposes only. In case the error it will showed and the result a node called "debug" will be added.
     *
     * @param string $key
     * @param mixed $string
     */
    public function writeDebug($key, $string)
    {
        if (is_null($this->responseDebug))
        {
            $this->responseDebug = new ResponseBag();
            $this->response->add($this->responseDebug);
        }
        $this->responseDebug->add(['debug' => [ $key => $string]]);
        ErrorHandler::getInstance()->addExtraInfo($key, serialize($string));
    }
}
