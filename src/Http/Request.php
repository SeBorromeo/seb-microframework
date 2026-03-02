<?php namespace SeBorromeo\SebMicroframework\Http;

use SeBorromeo\SebMicroframework\Application;
use SeBorromeo\SebMicroframework\Routing\Route;

class Request {
    private ?array $body = null;
    private array $headers; 
    private array $params = [];
    private array $query = [];
    private array $attributes = [];

    private array $cookies = []; // Write cookieparser
    private array $signedCookies = [];

    private array $ips; // TODO
    private array $subdomains; // TODO

    private Route $route; 
    private string $url;
    private string $path;
    private string $baseUrl = '/';
    private int $port;

    public readonly Response $res;
    public readonly HttpMethod $method;
    public readonly string $uri;
    public readonly string $originalUrl;
    public readonly string $host;
    public readonly string $hostname;
    public readonly string $protocol;
    public readonly bool $secure;
    public readonly bool $xhr;
    public readonly ?string $ip;

    public function __construct(
        public readonly Application $app,
        array $requestMeta
    ) {
        $requireMeta = function(string $key) use ($requestMeta): mixed {
            if (!isset($requestMeta[$key])) 
                throw new \InvalidArgumentException("Missing required Request parameter: {$key}");

            return $requestMeta[$key];
        };

        $this->method = HttpMethod::fromString(
            strtoupper($requireMeta('REQUEST_METHOD'))
        );

        $this->uri = $requireMeta('REQUEST_URI');
        $this->originalUrl = $this->uri;
        $this->url = $this->uri;

        $this->path = $requireMeta('PATH_INFO');

        $this->host = $requireMeta('HTTP_HOST');
        $this->hostname = explode(':', $this->host)[0];

        $this->port = (int) ($requireMeta('SERVER_PORT'));

        $this->protocol = (!empty($requestMeta['HTTPS']) && $requestMeta['HTTPS'] !== 'off')
            ? 'https'
            : 'http';

        $this->secure = $this->protocol === 'https' ? true : false;

        $this->ip = $requestMeta['HTTP_X_FORWARDED_FOR'];

        parse_str($requestMeta['QUERY_STRING'] ?? '', $this->query);
        
        $this->xhr = $requestMeta['X-REQUESTED-WITH'] === 'xmlhttprequest';

        $this->headers = $this->parseHeaders($requestMeta);
    }

    public function setResponse(Response $res): void { $this->res = $res; }

    /* ---------- Accepts ---------- */

    public function accepts(string|array $contentType): string|bool|null {
        // TODO
        return null;
    }
    
    public function acceptsCharsets(array $charsets): string|bool {
        return '';
    }
    
    public function acceptsEncodings(array $charsets): string|bool {
        return '';
    }
    
    public function acceptsLangauges(array $charsets): string|bool {
        return '';
    }

    /* ---------- Base URL ---------- */

    public function baseUrl(): string { return $this->baseUrl; }

    /** @internal */
    public function setBaseUrl(string $baseUrl): void { $this->baseUrl = $baseUrl; }

    /** @internal */
    public function addToBaseUrl(string $addition): void { $this->baseUrl .=  $addition; }

    /* ---------- URL ---------- */

    public function url(): string { return $this->url; }

    /**
     * Rewrite url for routing purposes (original stored in readonly property ->originalUrl).
     * 
     * @internal
    */
    public function setUrl(string $url): void { $this->url = $url; }

    /* ---------- Path ---------- */

    public function path(): string { return $this->path; }

    /**
     * Rewrite path for routing purposes.
     * 
     * @internal
    */
    public function setPath(string $path): void { $this->path = $path; }

    /* ---------- Port ---------- */

    /** @internal */
    public function port() { return $this->port; }

    /* ---------- Fresh/Stale ---------- */

    public function isFresh(): bool {
        if ($this->method !== 'GET' && $this->method !== 'HEAD')
            return false;

        $status = $this->res->statusCode();
        if (!(($status >= 200 && $status < 300) || $status === 304)) 
            return false;

        $ifNoneMatch = $this->getHeader('if-none-match');
        $ifModifiedSince = $this->getHeader('if-modified-since');

        $etag = $this->res->getHeader('ETag');
        $lastModified = $this->res->getHeader('Last-Modified');

        if ($etag && $ifNoneMatch) {
            $etags = array_map('trim', explode(',', $ifNoneMatch));
            if (in_array($etag, $etags) || in_array('*', $etags)) {
                return true;
            }
        }

        if ($lastModified && $ifModifiedSince) {
            $lastModifiedTime = strtotime($lastModified);
            $ifModifiedSinceTime = strtotime($ifModifiedSince);

            if ($lastModifiedTime <= $ifModifiedSinceTime) {
                return true;
            }
        }

        return false;
    }

    public function isStale(): bool {
        return !$this->isFresh();
    }

    /* ---------- Query Parameters ---------- */

    public function query(?string $key = null) {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key];
    }

    /* ---------- Route Parameters (Set by Router) ---------- */

    public function params() { return $this->params; }

    public function param(string $key) {
        return $this->params[$key];
    }

    /** @internal */
    public function setParams(array $params): void {
        $this->params = $params;
    }

    /** @internal */
    public function setParam(string $key, string $val): void {
        $this->params[$key] = $val;
    }

    /* ---------- Route (Set by Router) ---------- */

    public function route() { return $this->route; }

    /** @internal */
    public function setRoute(Route $route) { $this->route = $route; }

    /* ---------- Cookies ---------- */

    // TODO: THIS GOES IN COOKIE PARSER
    public function cookies(): array { return $this->cookies; }

    public function signedCookies(): array { return $this->signedCookies; }

    private function parseCookies(string $cookieHeader): void {
        $pairs = explode(';', $cookieHeader);

        foreach ($pairs as $pair) {
            [$name, $value] = array_map('trim', explode('=', $pair, 2) + [1 => '']);

            $this->cookies[$name] = $value;

            if (str_contains($value, '.')) {
                [$val, $sig] = explode('.', $value, 2);
                if ($this->verifySignature($name, $val, $sig)) {
                    $this->signedCookies[$name] = $val;
                }
            }
        }
    }

    private function verifySignature(string $name, string $value, string $signature): bool {
        $expected = hash_hmac('sha256', $name . '=' . $value, $this->secret);
        return hash_equals($expected, $signature);
    }

    /* ---------- Body/Input Data ---------- */
    
    public function input(?string $key = null) {
        $body = $this->body();

        if ($key === null) {
            return $body;
        }

        return $body[$key];
    }

    public function all(): array {
        return array_merge($this->query, $this->body());
    }

    public function has(string $key): bool {
        return isset($this->body()[$key]) || isset($this->query[$key]);
    }

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

    public function get(string $name): ?string { return $this->getHeader($name); }
    public function getHeader(string $name): ?string {
        $name = strtolower($name);
        if ($name == 'referer' || $name == 'referrer') {
            return $this->headers['referer'] ?? $this->headers['referrer'];
        }
        return $this->headers[$name];
    }

    public function headers(): array { return $this->headers; }
    
    public function hasHeader(string $name): bool { 
        return isset($this->headers[strtolower($name)]); 
    }

    private function parseHeaders(array $requestMeta): array {
        $headers = [];

        foreach ($requestMeta as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                // HTTP_AUTHORIZATION -> authorization
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = $value;
            }
        }

        // Special cases (these don't have HTTP_ prefix)
        if (isset($requestMeta['CONTENT_TYPE'])) {
            $headers['content-type'] = $requestMeta['CONTENT_TYPE'];
        }
        if (isset($requestMeta['CONTENT_LENGTH'])) {
            $headers['content-length'] = $requestMeta['CONTENT_LENGTH'];
        }

        return $headers;
    }

    /* ---------- Content Type ---------- */

    public function is(string|array $contentType): string|bool|null { return $this->isContentType($contentType); }
    public function isContentType(string|array $contentType): ?bool {
        $header = $this->getHeader('content-type');
        if (!$header) 
            return false;

        $actual = strtolower(trim(explode(';', $header)[0]));
        [$actualType, $actualSubtype] = explode('/', $actual);
        
        $contentTypes = (array) $contentType;
        foreach ($contentTypes as $c) {
            if (!is_string($c))
                throw new \InvalidArgumentException('Content type must be a string.');

            $type = strtolower(trim($c));

            if ($type === $actual || $type === $actualSubtype || $type === $actualType . '/*')
                return $type;
        }

        return false;
    }

    /* ---------- Magic Methods For Attributes ---------- */

    /**
     * Magic methods to allow user to add/edit custom Request object attributes directly.
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

    public function getAttribute(string $key) {
        return $this->attributes[$key];
    }
}