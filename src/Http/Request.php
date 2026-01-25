<?php namespace Sebastian\MicroFramework\Http;

class Request {
    private array $params = [];
    private array $query;   
    private ?array $body = null; // lazy loaded
    private array $headers;
    private array $server;

    private string $method;
    private string $path;
    private string $contentType;

    // Attributes bag for magic properties to hold extra data
    private array $attributes = [];

    public function __construct(array $server = null) {
        $this->server = $server ?? $_SERVER;
        $this->method = strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
        $this->path = parse_url($this->server['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $this->contentType = $this->parseContentType();
        $this->query = $this->parseQuery();
        $this->headers = $this->parseHeaders();
    }

    public static function createFromGlobals(): self {
        return new self($_SERVER);
    }

    // HTTP Method
    public function method(): string { return $this->method; }

    public function isMethod(string $method): bool {
        return $this->$method === strtoupper($method);
    }

    public function path(): string { return $this->path; }

    public function uri(): string { 
        return $this->server['REQUEST_URI'] ?? '/'; 
    }

    // Query parameters (?key=value)
    public function query(?string $key = null, $default = null) {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }
    
    // Route parameters (set by router)
    public function param(?string $key = null, $default = null) {
        if ($key === null) {
            return $this->params;
        }
        return $this->params[$key] ?? $default;
    }

    public function setParams(array $params): void {
        $this->params = $params;
    }

    // Body/Input data
    public function input(?string $key = null, $default = null)
    {
        $body = $this->body();

        if ($key === null) {
            return $body;
        }

        return $body[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body());
    }

    public function has(string $key): bool
    {
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
        if (in_array($this->method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            parse_str(file_get_contents('php://input'), $data);
            $this->body = $data;
            return $this->body;
        }

        $this->body = [];
        return $this->body;
    }

    // Headers
    public function header(string $name, $default = null): ?string { // TODO: alias with get
        $name = strtolower($name);
        return $this->headers[$name] ?? $default;
    }

    public function headers(): array { return $this->headers; }
    
    public function hasHeader(string $name): bool { 
        return isset($this->headers[strtolower($name)]); 
    }

    public function contentType(): string { return $this->contentType; }

    // Magic methods for attributes (e.g., $request->user = $user)
    public function __get(string $name) { 
        return $this->attributes[$name] ?? null; 
    }

    public function __set(string $name, $value): void { 
        if (property_exists($this, $name)) {
            throw new \LogicException("Cannot set '$name' - use proper methods instead");
        }

        $this->attributes[$name] = $value; 
    }
    
    public function __isset(string $name): bool { 
        return isset($this->attributes[$name]); 
    }

    public function __unset(string $name): void { 
        unset($this->attributes[$name]); 
    }

    // Alternative explicit methods for attributes
    public function setAttribute(string $key, $value): void {
        $this->attributes[$key] = $value;
    }

    public function getAttribute(string $key, $default = null) {
        return $this->attributes[$key] ?? $default;
    }


    // Private helpers
    private function parseContentType(): string {
        $contentType = $this->server['CONTENT_TYPE'] ?? '';
        return trim(explode(';', $contentType)[0]);
    }

    private function parseQuery(): array {
        parse_str($this->server['QUERY_STRING'] ?? '', $query);
        return $query;
    }

    private function parseHeaders(): array {
        $headers = [];

        foreach ($this->server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                // HTTP_AUTHORIZATION -> authorization
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = $value;
            }
        }

        // Special cases (these don't have HTTP_ prefix)
        if (isset($this->server['CONTENT_TYPE'])) {
            $headers['content-type'] = $this->server['CONTENT_TYPE'];
        }
        if (isset($this->server['CONTENT_LENGTH'])) {
            $headers['content-length'] = $this->server['CONTENT_LENGTH'];
        }

        return $headers;
    }
}