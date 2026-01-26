<?php namespace Sebastian\MicroFramework\Http;

use Sebastian\MicroFramework\Application;

class Response {
    private Application $app;
    private bool $headersSent = false;
    private bool $ended = false;
    private array $locals;
    
    private array $headers = [];
    private bool $headersSent = False;
    private int $statusCode = 200;
    public string $statusMessage = 'OK';
    private string $body = "";

    private static array $statusTexts = [
        // 1xx Informational
        100 => 'Continue',
        101 => 'Switching Protocols',
        // 2xx Success
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        204 => 'No Content',
        // 3xx Redirection
        301 => 'Moved Permanently',
        302 => 'Found',
        304 => 'Not Modified',
        // 4xx Client Error
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        // 5xx Server Error
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
    ];

    public function __construct(Application $app) {
        $this->app = $app;
    }

    /* ---------- Headers ---------- */
    
    public function getHeader(string $name): string|array|null {
        return $this->headers[$name];
    }
        
    public function getHeaderNames(): array {
        return array_keys($this->headers);
    }
    
    public function getHeaders(): array {
        return $this->headers;
    }
    
    public function hasHeader($name): bool {
        return isset($this->headers[$name]);
    }
        
    public function removeHeader($name): void {
        unset($this->headers[$name]);
    }
        
    public function headersSent(): bool { return $this->headersSent; }
    
    public function header(string|array $nameOrObject, string|array|null $value = null): void { $this->set($nameOrObject, $value); }
    public function set(string|array $nameOrObject, string|array|null $value = null): void {
        if (is_array($nameOrObject)) {
            foreach ($nameOrObject as $h => $v) {
                $this->headers[$h] = $v;
            } 
        } else {
            $this->headers[$nameOrObject] = $value;
        }
    }

    public function append(string $name, string|array $firstArg, string ...$extraArgs): void {
        $valuesToAdd = is_array($firstArg) ? $firstArg : array_merge([$firstArg], $extraArgs);

        if (!$this->hasHeader($name)) {
            $this->set($name, count($valuesToAdd) === 1 ? $valuesToAdd[0] : $valuesToAdd);
        }
        else {
            $existing = is_array($this->headers[$name]) ? $this->headers[$name] : [$this->headers[$name]];
            $this->headers[$name] = array_merge($existing, $valuesToAdd);
        }
    }

    /* ---------- _______ ---------- */

    public function app(): Application { return $this->app; }
    public function locals(): array { return $this->locals; }
    public function writeableEnded(): bool { return $this->writeableEnded; }

    public function addLocalVar($var): void {
        $this->locals[] = $var;
    }


    public function attachment(): void {

    }
            
    /* ---------- Status ---------- */

    public function status(int $code): Response {
        $this->statusCode = $code;
        $this->statusMessage = self::$statusTexts[$code] ?? '';
        return $this;
    }

    public function statusMessage(): string { return $this->statusMessage; }

}