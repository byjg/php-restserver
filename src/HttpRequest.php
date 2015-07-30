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

    protected function __construct($get, $post, $server, $session, $cookie)
    {
        $this->get = $get;
        $this->post = $post;
        $this->server = $server;
        $this->session = $session;
        $this->cookie = $cookie;

        $this->request = array_merge($get, $post, $server, $session, $cookie);
    }

    public function get($value)
    {
        if (!isset($this->get[$value])) {
            return false;
        }
        else {
            return $this->get[$value];
        }
    }

    public function post($value)
    {
        if (!isset($this->post[$value])) {
            return false;
        }
        else {
            return $this->post[$value];
        }
    }

    public function server($value)
    {
        if (!isset($this->server[$value])) {
            return false;
        }
        else {
            return $this->server[$value];
        }
    }

    public function session($value)
    {
        if (!isset($this->session[$value])) {
            return false;
        }
        else {
            return $this->session[$value];
        }
    }

    public function cookie($value)
    {
        if (!isset($this->cookie[$value])) {
            return false;
        }
        else {
            return $this->cookie[$value];
        }
    }

    public function request($value)
    {
        if (!isset($this->request[$value])) {
            return false;
        }
        else {
            return $this->request[$value];
        }
    }

	/**
	 * Use this method to get the CLIENT REQUEST IP.
	 * Note that if you behing a Proxy, the variable REMOTE_ADDR will always have the same IP
	 * @return string
	 */
	public function getRequestIp()
	{
		$ipaddress = '';
		if (isset($this->server('HTTP_CLIENT_IP'))) {
            $ipaddress = $this->server('HTTP_CLIENT_IP');
        } else if (isset($this->server('HTTP_X_FORWARDED_FOR'))) {
            $ipaddress = $this->server('HTTP_X_FORWARDED_FOR');
        } else if (isset($this->server('HTTP_X_FORWARDED'))) {
            $ipaddress = $this->server('HTTP_X_FORWARDED');
        } else if (isset($this->server('HTTP_FORWARDED_FOR'))) {
            $ipaddress = $this->server('HTTP_FORWARDED_FOR');
        } else if (isset($this->server('HTTP_FORWARDED'))) {
            $ipaddress = $this->server('HTTP_FORWARDED');
        } else if (isset($this->server('REMOTE_ADDR'))) {
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
		if (isset($this->server('SERVER_NAME')) && !empty($this->server('SERVER_NAME'))) {
            $servername = $this->server('SERVER_NAME');
        } else if (isset($this->server('HTTP_HOST')) && !empty($this->server('HTTP_HOST'))) {
            $servername = $this->server('HTTP_HOST');
        } else {
            $servername = $this->server('SERVER_ADDR');
        }

        if ($port && isset($this->server('SERVER_PORT'))) {
            $servername .= ':' . $this->server('SERVER_PORT');
        }

        if ($protocol) {
            $servername = ((!empty($this->server('HTTPS')) && $this->server('HTTPS') !== 'off' || $this->server('SERVER_PORT') == 443) ? "https://" : "http://") . $servername;
        }

        return $servername;
	}
}
