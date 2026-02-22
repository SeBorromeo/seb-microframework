<?php namespace SeBorromeo\SebMicroframework\Http;

use SeBorromeo\SebMicroframework\Application;
use SeBorromeo\SebMicroframework\Routing\Route;

class Request {
    private array $params;
    private array $query;   

    /** @var array<string, string> (Lazy loaded) */
    private ?array $body = null;

    private array $headers;
    private array $serverParams;
    private array $attributes = [];

    public readonly HttpMethod $method;
    public readonly string $path;
    private string $contentType;
    
    private array $cookies = [];
    private array $signedCookies = [];

    public readonly bool $fresh;

    
    public readonly string $host;
    public readonly string $uri;
    public readonly string $hostname;
    public readonly ?string $ip;
    private Response $res;
    public readonly string $protocol;
    public Route $route;
    public readonly bool $secure;

    private string $baseURl;

    
    public function __construct(
        array|null $serverParams = null,
        public readonly Application $app,

    ) {
        $this->serverParams = $serverParams ?? $_SERVER;
        $this->method = HttpMethod::fromString(strtoupper($this->serverParams['REQUEST_METHOD'] ?? 'GET'));

        $this->protocol = (!empty($this->serverParams['HTTPS']) && $this->serverParams['HTTPS'] !== 'off')
            ? 'https'
            : 'http';

        $this->secure = $this->protocol === 'https' ? true : false;

        $this->host = $this->serverParams['HTTP_HOST']
            ?? $this->serverParams['SERVER_NAME']
            ?? 'localhost';

        $this->port = (int) ($this->serverParams['SERVER_PORT']);
        if (!$this->port) {
            $this->port = $this->secure ? 443 : 80;
        }

        $host = $this->serverParams['HTTP_HOST']
            ?? $this->serverParams['SERVER_NAME']
            ?? 'localhost';

        $this->hostname = explode(':', $host)[0];

        $this->ip = $this->serverParams['HTTP_X_FORWARDED_FOR']
            ?? $this->serverParams['REMOTE_ADDR']
            ?? null;

        $this->uri = $this->serverParams['REQUEST_URI'] ?? '/';
            
        $this->path = parse_url($this->serverParams['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        $this->contentType = $this->parseContentType();
        
        $this->query = $this->parseQuery();
        
        $this->headers = $this->parseHeaders();
    }

    // HTTP Method
    public function uri(): string { 
        return $this->serverParams['REQUEST_URI'] ?? '/'; 
    }

    /* ---------- Query Parameters ---------- */

    private function parseQuery(): array {
        parse_str($this->serverParams['QUERY_STRING'] ?? '', $query);
        return $query;
    }

    public function query(?string $key = null, $default = null) {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }
    
    /* ---------- Route Parameters (Set by Router) ---------- */

    public function params() { return $this->params; }

    public function param(string $key, $default = null) {
        return $this->params[$key] ?? $default;
    }

    public function setParams(array $params): void {
        $this->params = $params;
    }

    public function setParam(string $key, string $val): void {
        $this->params[$key] = $val;
    }

    /* ---------- Cookies ---------- */

    public function cookies(): array { return $this->cookies; }

    public function signedCookies(): array { return $this->signedCookies; }

    /* ---------- Body/Input Data ---------- */
    
    public function input(?string $key = null, $default = null) {
        $body = $this->body();

        if ($key === null) {
            return $body;
        }

        return $body[$key] ?? $default;
    }

    public function all(): array {
        return array_merge($this->query, $this->body());
    }

    public function has(string $key): bool {
        return isset($this->body()[$key]) || isset($this->query[$key]);
    }

    // TODO:
    // public function is(string|array $val): string {

    // }

    public function json(): array {
        if ($this->body === null) {
            $raw = file_get_contents('php://input');
            $this->body = json_decode($raw, true) ?? [];
        }
        return $this->body;
    }

    // TODO: Look at this specifically regarding BodyParser Middleware
    private function body(): array {
        if ($this->body !== null) {
            return $this->body;
        }

        // Parse based on content type
        if (str_contains($this->contentType, 'application/json')) {
            return $this->json();
        }

        // Form data
        if (in_array($this->method, [HttpMethod::POST, HttpMethod::PUT, HttpMethod::PATCH, HttpMethod::DELETE])) {
            parse_str(file_get_contents('php://input'), $data);
            $this->body = $data;
            return $this->body;
        }

        $this->body = [];
        return $this->body;
    }

    /* ---------- Headers ---------- */

    public function header(string $name, $default = null): ?string { // TODO: alias with get
        $name = strtolower($name);
        return $this->headers[$name] ?? $default;
    }

    public function headers(): array { return $this->headers; }
    
    public function hasHeader(string $name): bool { 
        return isset($this->headers[strtolower($name)]); 
    }

    public function contentType(): string { return $this->contentType; }

    /* ---------- Magic Methods For Attributes ---------- */

    /**
     * (e.g., $request->user = $user)
     */

    public function __get(string $name) { 
        return $this->attributes[$name] ?? null; 
    }

    public function __set(string $name, $value): void { 
        if (property_exists($this, $name)) {
            throw new \LogicException("Cannot set Request->$name. Property of the same name already exists. Use proper methods to modify instead.");
        }

        $this->attributes[$name] = $value; 
    }
    
    public function __isset(string $name): bool { 
        return isset($this->attributes[$name]); 
    }

    public function __unset(string $name): void { 
        unset($this->attributes[$name]); 
    }

    /* ---------- Alternative Explicit Methods For Attributes ---------- */

    public function setAttribute(string $key, $value): void {
        $this->attributes[$key] = $value;
    }

    public function getAttribute(string $key, $default = null) {
        return $this->attributes[$key] ?? $default;
    }

    /* ---------- Private Helpers ---------- */

    private function parseContentType(): string {
        $contentType = $this->serverParams['CONTENT_TYPE'] ?? '';
        return trim(explode(';', $contentType)[0]);
    }

    private function parseHeaders(): array {
        $headers = [];

        foreach ($this->serverParams as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                // HTTP_AUTHORIZATION -> authorization
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = $value;
            }
        }

        // Special cases (these don't have HTTP_ prefix)
        if (isset($this->serverParams['CONTENT_TYPE'])) {
            $headers['content-type'] = $this->serverParams['CONTENT_TYPE'];
        }
        if (isset($this->serverParams['CONTENT_LENGTH'])) {
            $headers['content-length'] = $this->serverParams['CONTENT_LENGTH'];
        }

        return $headers;
    }
}