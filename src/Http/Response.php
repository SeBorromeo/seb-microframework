<?php namespace Sebastian\MicroFramework\Http;

use Sebastian\MicroFramework\Application;

class Response {
    private Application $app;
    private array $locals = [];
    
    private array $headers = [];
    private bool $headersSent = False;
    private bool $ended = False;
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

    public function app(): Application { return $this->app; }

    /* ---------- Headers ---------- */
    
    public function get(string $name): string|array|null { return $this->getHeader($name); }
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
        
    public function set(string|array $nameOrObject, string|array|null $value = null): void { $this->header($nameOrObject, $value); }
    public function header(string|array $nameOrObject, string|array|null $value = null): void {
        if ($this->headersSent)
            throw new \LogicException('Cannot set headers after they are sent');

        if (is_array($nameOrObject)) {
            foreach ($nameOrObject as $h => $v) {
                $this->headers[$h] = $v;
            } 
        } else {
            $this->headers[$nameOrObject] = $value;
        }
    }

    public function append(string $name, string|array $value): void {
        if ($this->headersSent)
            throw new \LogicException('Cannot set headers after they are sent');    

        if (!$this->hasHeader($name)) {
            $this->set($name, $value);
        } else {
            $existing = is_array($this->headers[$name]) ? $this->headers[$name] : [$this->headers[$name]];
            $valuesToAdd = is_array($value) ? $value : [$value];
            $this->headers[$name] = array_merge($existing, $valuesToAdd);
        }
    }

    public function headersSent(): bool { return $this->headersSent; }

    /* ---------- Header Shorthands ---------- */

    // TODO: attachment, format, links, location, type, vary

    /* ---------- Status ---------- */

    public function status(int $code): Response {
        $this->statusCode = $code;
        $this->statusMessage = self::$statusTexts[$code] ?? '';
        return $this;
    }

    public function statusMessage(): string { return $this->statusMessage; }


    /* ---------- Send ---------- */

    public function send(array|string|bool|null $body = null): void {
        if ($this->ended)
            throw new \LogicException('Response already ended');
        elseif ($this->headersSent)
            throw new \LogicException('Cannot send headers after they are already sent');

        $this->sendHeaders();
        
        if ($body)
            echo $body;
        elseif ($this->body) 
            echo $this->body;
    }

    private function sendHeaders(): void {
        header(sprintf('HTTP/1.1 %d %s', $this->statusCode, $this->statusMessage), true, $this->statusCode);

        foreach ($this->headers as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    // TODO: seperate between headers that are comma separated vs. needs separate lines 
                    header("$name: $v", false);
                }
            } else {
                header("$name: $value", true);
            }
        }
    }

    public function sendStatus(int $statusCode): void {
        $this->status($statusCode)->send();
    }

    // TODO: Implement with Swoole later to stream chunks of files
    public function sendFile() { }
    public function download(): void {}

}