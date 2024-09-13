<?php

namespace ByJG\RestServer;

class HttpRequest
{

    protected array $get;
    protected array $post;
    protected array $server;
    protected array $session;
    protected array $cookie;
    protected array $param;
    protected array $phpRequest;

    /**
     *
     * @param array $get
     * @param array $post
     * @param array $server
     * @param array $session
     * @param array $cookie
     * @param array $param
     */
    public function __construct(array $get, array $post, array $server, array $session, array $cookie, array $param = [])
    {
        $this->get = $get;
        $this->post = $post;
        $this->server = $server;
        $this->session = $session;
        $this->cookie = $cookie;
        $this->param = $param;

        $this->phpRequest = array_merge($get, $post, $server, $session, $cookie);
    }

    /**
     * Get a parameter passed by GET (the same as $_GET). If not found return false.
     *
     * @param string|null $value
     * @param bool $default
     * @return string|array|boolean
     */
    public function get(?string $value = null, bool $default = false): string|array|bool
    {
        if (is_null($value)) {
            return $this->get;
        }

        if (!isset($this->get[$value])) {
            return $default;
        } else {
            return $this->get[$value];
        }
    }

    /**
     * Get a parameter passed by POST (the same as $_POST). If not found return false.
     *
     * @param ?string $value
     * @param bool $default
     * @return string|boolean|array
     */
    public function post(?string $value = null, bool $default = false): string|array|bool
    {
        if (is_null($value)) {
            return $this->post;
        }

        if (!isset($this->post[$value])) {
            return $default;
        } else {
            return $this->post[$value];
        }
    }

    /**
     * Get the parameters sent by server (the same as $_SERVER). If not found return false.
     *
     * @param ?string $value
     * @param bool $default
     * @return string|boolean|array
     */
    public function server(?string $value = null, bool $default = false): string|array|bool
    {
        if (is_null($value)) {
            return $this->server;
        }

        if (!isset($this->server[$value])) {
            return $default;
        } else {
            return $this->server[$value];
        }
    }

    /**
     * Get a server session value(the same as $_SESSION). If not found return false.
     *
     * @param ?string $value
     * @param bool $default
     * @return string|boolean|array
     */
    public function session(?string $value = null, bool $default = false): bool|array|string
    {
        if (is_null($value)) {
            return $this->session;
        }

        if (!isset($this->session[$value])) {
            return $default;
        } else {
            return $this->session[$value];
        }
    }

    /**
     * Get the cookie sent by the client (the same as $_COOKIE). If not found return false.
     *
     * @param ?string $value
     * @param bool $default
     * @return string|boolean|array
     */
    public function cookie(?string $value = null, bool $default = false): bool|array|string
    {
        if (is_null($value)) {
            return $this->cookie;
        }

        if (!isset($this->cookie[$value])) {
            return $default;
        } else {
            return $this->cookie[$value];
        }
    }

    /**
     * Get a value from any of get, post, server, cookie or session. If not found return false.
     *
     * @param ?string $value
     * @param bool $default
     * @return string|boolean|array
     */
    public function request(?string $value = null, bool $default = false): bool|array|string
    {
        if (is_null($value)) {
            return $this->phpRequest;
        }

        if (!isset($this->phpRequest[$value])) {
            return $default;
        } else {
            return $this->phpRequest[$value];
        }
    }

    /**
     * Get a value from the params found in the URL
     *
     * @param ?string $value
     * @param bool $default
     * @return mixed
     */
    public function param(?string $value = null, bool $default = false): mixed
    {
        if (is_null($value)) {
            return $this->param;
        }

        if (!isset($this->param[$value])) {
            return $default;
        } else {
            return $this->param[$value];
        }
    }

    protected ?string $payload = null;

    /**
     * Get the payload passed during the request(the same as php://input). If not found return empty.
     *
     * @return string
     */
    public function payload(): string
    {
        if (is_null($this->payload)) {
            $this->payload = file_get_contents("php://input");
        }

        return $this->payload;
    }

    /**
     * Use this method to get the CLIENT REQUEST IP.
     * Note that if you behing a Proxy, the variable REMOTE_ADDR will always have the same IP
     * @return string|null
     */
    public function getRequestIp(): ?string
    {
        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_ORIGINAL_FORWARDED_FOR',
            'REMOTE_ADDR',
            'SERVER_ADDR',
            'HTTP_CLIENT_IP',
        ];
        foreach ($headers as $header) {
            if ($this->server($header) !== false) {
                $list = explode(",", $this->server($header));
                return reset($list);
            }
        }

        return null;
    }

    public static function ip(): ?string
    {
        $request = new HttpRequest([], [], $_SERVER, [], []);
        return $request->getRequestIp();
    }

    /**
     * @return array|null|string|true
     */
    public function getUserAgent(): bool|array|string|null
    {
        $userAgent = $this->server('HTTP_USER_AGENT');
        return $userAgent ?: null;
    }

    public static function userAgent(): bool|array|string|null
    {
        $request = new HttpRequest([], [], $_SERVER, [], []);
        return $request->getUserAgent();
    }

    public function getServerName(): bool|array|string
    {
        $headers = [
            'SERVER_NAME',
            'HTTP_HOST',
        ];
        foreach ($headers as $header) {
            if ($this->server($header) !== false) {
                return $this->server('SERVER_NAME');
            }
        }
        return $this->server('SERVER_ADDR');
    }

    /**
     * Use this method to get the SERVER NAME.
     * @param bool $port
     * @param bool $protocol
     * @return bool|array<array-key, mixed>|string
     */
    public function getRequestServer(bool $port = false, bool $protocol = false): bool|array|string
    {
        $servername = $this->getServerName();

        if ($port && $this->server('SERVER_PORT') !== false) {
            $servername .= ':' . $this->server('SERVER_PORT');
        }

        if ($protocol) {
            $servername = (
                ($this->server('HTTPS') !== 'off'
                    || $this->server('SERVER_PORT') == 443) ? "https://" : "http://") . $servername
            ;
        }

        return $servername;
    }

    public function getHeader(string $header): bool|array|string
    {
        $header = strtoupper(str_replace('-', '_', $header));
        $header = 'HTTP_' . $header;
        return $this->server($header);
    }

    /**
     * @return false|null|string
     */
    public function getRequestPath(): bool|array|string|null
    {
        return parse_url($this->server('REQUEST_URI'), PHP_URL_PATH);
    }

    private ?UploadedFiles $uploadedFiles = null;

    /**
     * @return UploadedFiles
     */
    public function uploadedFiles(): UploadedFiles
    {
        if (is_null($this->uploadedFiles)) {
            $this->uploadedFiles = new UploadedFiles();
        }
        return $this->uploadedFiles;
    }

    public function appendVars(array $array): void
    {
        $this->param = array_merge($this->param, $array);
    }
}
