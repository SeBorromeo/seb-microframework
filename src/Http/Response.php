<?php namespace Sebastian\MicroFramework\Http;

use Sebastian\MicroFramework\Application;

class Response {
    private Application $app;
    private bool $headersSent = false;
    private bool $ended = false;
    private array $locals;
    
    private array $headers = [];
    private int $statusCode = 200;
    private string $statusMessage;
    private string $body = "";

    private bool $writeableEnded;

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

    public function status(int $code): Response {
        $this->statusCode = $code;
        return $this;
    }
}