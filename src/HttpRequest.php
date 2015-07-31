<?php

namespace ByJG\RestServer;

class HttpRequest
{
    protected $get;
    protected $post;
    protected $server;
    protected $session;
    protected $cookie;

    protected $request;

    /**
     *
     * @param array $get
     * @param array $post
     * @param array $server
     * @param array $session
     * @param array $cookie
     */
    public function __construct($get, $post, $server, $session, $cookie)
    {
        $this->get = $get;
        $this->post = $post;
        $this->server = $server;
        $this->session = $session;
        $this->cookie = $cookie;

        $this->request = array_merge($get, $post, $server, $session, $cookie);
    }

    /**
     * Get a parameter passed by GET (the same as $_GET). If not found return false.
     *
     * @param string $value
     * @return string|boolean
     */
    public function get($value)
    {
        if (!isset($this->get[$value])) {
            return false;
        }
        else {
            return $this->get[$value];
        }
    }

    /**
     * Get a parameter passed by POST (the same as $_POST). If not found return false.
     *
     * @param string $value
     * @return string|boolean
     */
    public function post($value)
    {
        if (!isset($this->post[$value])) {
            return false;
        }
        else {
            return $this->post[$value];
        }
    }

    /**
     * Get the parameters sent by server (the same as $_SERVER). If not found return false.
     *
     * @param string $value
     * @return string|boolean
     */
    public function server($value)
    {
        if (!isset($this->server[$value])) {
            return false;
        }
        else {
            return $this->server[$value];
        }
    }

    /**
     * Get a server session value(the same as $_SESSION). If not found return false.
     *
     * @param string $value
     * @return string|boolean
     */
    public function session($value)
    {
        if (!isset($this->session[$value])) {
            return false;
        }
        else {
            return $this->session[$value];
        }
    }

    /**
     * Get the cookie sent by the client (the same as $_COOKIE). If not found return false.
     *
     * @param string $value
     * @return string|boolean
     */
    public function cookie($value)
    {
        if (!isset($this->cookie[$value])) {
            return false;
        }
        else {
            return $this->cookie[$value];
        }
    }

    /**
     * Get a value from any of get, post, server, cookie or session. If not found return false.
     *
     * @param string $value
     * @return string|boolean
     */
    public function request($value)
    {
        if (!isset($this->request[$value])) {
            return false;
        }
        else {
            return $this->request[$value];
        }
    }

    private $payload;

    /**
     * Get the payload passed during the request(the same as php://input). If not found return empty.
     *
     * @return string
     */
    public function payload()
    {
		if (is_null($this->payload))
		{
			$this->payload = file_get_contents("php://input");
		}

		return $this->payload;
    }

	/**
	 * Use this method to get the CLIENT REQUEST IP.
	 * Note that if you behing a Proxy, the variable REMOTE_ADDR will always have the same IP
	 * @return string
	 */
	public function getRequestIp()
	{
		$ipaddress = '';
		if ($this->server('HTTP_CLIENT_IP') !== false) {
            $ipaddress = $this->server('HTTP_CLIENT_IP');
        } else if ($this->server('HTTP_X_FORWARDED_FOR') !== false) {
            $ipaddress = $this->server('HTTP_X_FORWARDED_FOR');
        } else if ($this->server('HTTP_X_FORWARDED') !== false) {
            $ipaddress = $this->server('HTTP_X_FORWARDED');
        } else if ($this->server('HTTP_FORWARDED_FOR') !== false) {
            $ipaddress = $this->server('HTTP_FORWARDED_FOR');
        } else if ($this->server('HTTP_FORWARDED') !== false) {
            $ipaddress = $this->server('HTTP_FORWARDED');
        } else if ($this->server('REMOTE_ADDR') !== false) {
            $ipaddress = $this->server('REMOTE_ADDR');
        } else {
            $ipaddress = 'UNKNOWN';
        }

        return $ipaddress;
	}

	/**
	 * Use this method to get the SERVER NAME.
	 * @return string
	 */
	public function getRequestServer($port = false, $protocol = false)
	{
		$servername = '';
		if ($this->server('SERVER_NAME') !== false) {
            $servername = $this->server('SERVER_NAME');
        } else if ($this->server('HTTP_HOST' !== false)) {
            $servername = $this->server('HTTP_HOST');
        } else {
            $servername = $this->server('SERVER_ADDR');
        }

        if ($port && $this->server('SERVER_PORT' !== false)) {
            $servername .= ':' . $this->server('SERVER_PORT');
        }

        if ($protocol) {
            $servername = (($this->server('HTTPS') !== 'off' || $this->server('SERVER_PORT') == 443) ? "https://" : "http://") . $servername;
        }

        return $servername;
	}
}
