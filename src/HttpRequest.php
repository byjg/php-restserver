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
    protected array $routeMetadata = [];

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
     * Get a value from the query string ($_GET). Returns all values if $value is null.
     * Returns $default if the key is not found.
     */
    public function query(?string $value = null, mixed $default = null): string|array|bool|null
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
     * Get a value from the request body ($_POST). Returns all values if $value is null.
     * Returns $default if the key is not found.
     */
    public function body(?string $value = null, mixed $default = null): string|array|bool|null
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
     * Get a value from the server parameters ($_SERVER). Returns all values if $value is null.
     * Returns $default if the key is not found.
     */
    public function server(?string $value = null, mixed $default = null): string|array|bool|null
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
     * Get a value from the session ($_SESSION). Returns all values if $value is null.
     * Returns $default if the key is not found.
     */
    public function session(?string $value = null, mixed $default = null): string|array|bool|null
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
     * Get a value from the cookies ($_COOKIE). Returns all values if $value is null.
     * Returns $default if the key is not found.
     */
    public function cookie(?string $value = null, mixed $default = null): string|array|bool|null
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
     * Get a value from any source (query, body, server, session, cookie merged).
     * Returns all values if $value is null. Returns $default if the key is not found.
     */
    public function input(?string $value = null, mixed $default = null): string|array|bool|null
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
     * Get a value injected by middleware or the framework (not URL or HTTP input).
     * Returns all values if $value is null. Returns $default if the key is not found.
     */
    public function attribute(?string $value = null, mixed $default = null): mixed
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
     * Get the raw request body (php://input). Returns an empty string if there is no body.
     */
    public function payload(): string
    {
        if (is_null($this->payload)) {
            $content = file_get_contents("php://input");
            $this->payload = $content !== false ? $content : '';
        }

        return $this->payload;
    }

    /**
     * Returns the client IP address, checking common proxy headers before REMOTE_ADDR.
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
            if ($this->server($header, false) !== false) {
                $value = $this->server($header);
                if (is_array($value)) {
                    $result = reset($value);
                    return $result !== false ? $result : null;
                }
                $list = explode(",", (string)$value);
                $result = reset($list);
                return $result !== false ? $result : null;
            }
        }

        return null;
    }

    public static function ip(): ?string
    {
        $request = new HttpRequest([], [], $_SERVER, [], []);
        return $request->getRequestIp();
    }

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

    public function getServerName(): bool|array|string|null
    {
        $headers = [
            'SERVER_NAME',
            'HTTP_HOST',
        ];
        foreach ($headers as $header) {
            if ($this->server($header, false) !== false) {
                return $this->server($header);
            }
        }
        return $this->server('SERVER_ADDR');
    }

    /**
     * Returns the server name, optionally with port and/or protocol.
     */
    public function getRequestServer(bool $port = false, bool $protocol = false): bool|array|string|null
    {
        $servername = $this->getServerName();

        if ($port && $this->server('SERVER_PORT', false) !== false) {
            $serverPort = $this->server('SERVER_PORT');
            $servername = (is_array($servername) ? '' : (string)$servername) . ':' . (is_array($serverPort) ? '' : (string)$serverPort);
        }

        if ($protocol) {
            $servername = (
                ($this->server('HTTPS') !== 'off'
                    || $this->server('SERVER_PORT') == 443) ? "https://" : "http://") . (is_array($servername) ? '' : (string)$servername)
            ;
        }

        return $servername;
    }

    public function getHeader(string $header): bool|array|string|null
    {
        $header = strtoupper(str_replace('-', '_', $header));
        $header = 'HTTP_' . $header;
        return $this->server($header);
    }

    public function getRequestPath(): ?string
    {
        $requestUri = $this->serverString('REQUEST_URI', "");
        $path = parse_url($requestUri ?? "", PHP_URL_PATH);
        return $path !== false ? $path : null;
    }

    private ?UploadedFiles $uploadedFiles = null;

    public function uploadedFiles(): UploadedFiles
    {
        if (is_null($this->uploadedFiles)) {
            $this->uploadedFiles = new UploadedFiles();
        }
        return $this->uploadedFiles;
    }

    /** Bulk-add values to the context bag (middleware/framework use). */
    public function addAttributes(array $array): void
    {
        $this->param = array_merge($this->param, $array);
    }

    /** Returns the query string value for $key as a string, or $default if not found. */
    public function queryString(string $key, ?string $default = null): ?string
    {
        $value = $this->query($key);
        if ($value === null || $value === false) {
            return $default;
        }
        return is_array($value) ? implode(',', $value) : (string)$value;
    }

    /** Returns the query string value for $key as an array, or $default if not found. */
    public function queryArray(string $key, ?array $default = null): ?array
    {
        $value = $this->query($key);
        if ($value === null || $value === false) {
            return $default;
        }
        return is_array($value) ? $value : [$value];
    }

    /** Returns the body value for $key as a string, or $default if not found. */
    public function bodyString(string $key, ?string $default = null): ?string
    {
        $value = $this->body($key);
        if ($value === null || $value === false) {
            return $default;
        }
        return is_array($value) ? implode(',', $value) : (string)$value;
    }

    /** Returns the body value for $key as an array, or $default if not found. */
    public function bodyArray(string $key, ?array $default = null): ?array
    {
        $value = $this->body($key);
        if ($value === null || $value === false) {
            return $default;
        }
        return is_array($value) ? $value : [$value];
    }

    /** Returns the $_SERVER value for $key as a string, or $default if not found. */
    public function serverString(string $key, ?string $default = null): ?string
    {
        $value = $this->server($key);
        if ($value === null || $value === false) {
            return $default;
        }
        return is_array($value) ? implode(',', $value) : (string)$value;
    }

    /** Returns the cookie value for $key as a string, or $default if not found. */
    public function cookieString(string $key, ?string $default = null): ?string
    {
        $value = $this->cookie($key);
        if ($value === null || $value === false) {
            return $default;
        }
        return is_array($value) ? implode(',', $value) : (string)$value;
    }

    /** Returns the session value for $key as a string, or $default if not found. */
    public function sessionString(string $key, ?string $default = null): ?string
    {
        $value = $this->session($key);
        if ($value === null || $value === false) {
            return $default;
        }
        return is_array($value) ? implode(',', $value) : (string)$value;
    }

    /** Returns the context value for $key as a string, or $default if not found. */
    public function attributeString(string $key, ?string $default = null): ?string
    {
        $value = $this->attribute($key);
        if ($value === null || $value === false) {
            return $default;
        }
        return is_array($value) ? implode(',', $value) : (string)$value;
    }

    /** Returns the merged input value for $key as a string, or $default if not found. */
    public function inputString(string $key, ?string $default = null): ?string
    {
        $value = $this->input($key);
        if ($value === null || $value === false) {
            return $default;
        }
        return is_array($value) ? implode(',', $value) : (string)$value;
    }

    public function routeMethod(): ?string
    {
        $value = $this->server('REQUEST_METHOD', 'GET');
        if (is_array($value)) {
            return $value[0];
        }
        if (is_bool($value)) {
            return 'GET';
        }
        return $value;
    }

    public function getRouteMetadata(?string $key = null): mixed
    {
        if (empty($key)) {
            return $this->routeMetadata;
        }

        return $this->routeMetadata[$key] ?? null;
    }

    public function setRouteMetadata(array $routeMetadata): void
    {
        $this->routeMetadata = $routeMetadata;
    }
}