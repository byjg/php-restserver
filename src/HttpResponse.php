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
	* @access public
	* @param string $name
	* @param string $value
	* @return string
	* @desc Add a value in session
	*/
	public function setSession($name, $value)
	{
		$_SESSION[$name] = $value;
	}

	/**
	* @access public
	* @param string $name
	* @return void
	* @desc Remove a value in this session
	*/
	public function removeSession($name)
	{
		unset($_SESSION[$name]);
	}

	/**
	* @access public
	* @param string $name
	* @param string $value
	* @param int $expire (seconds from now)
	* @param int $path (directory into domain in which the cookie will be available on )
	* @return void
	* @desc Add a value in cookie
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
	* @access public
	* @param string $name
	* @return void
	* @desc Remove a cookie
	*/
	public function removeCookie($name)
	{
		setcookie($name, null, time() - 3600);
		unset($_COOKIE[$name]);
	}

    public function getResponseBag()
    {
        return $this->response;
    }

    public function write($object)
    {
        $this->response->add($object);
    }

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
