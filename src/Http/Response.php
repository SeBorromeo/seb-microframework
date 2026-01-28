<?php namespace Sebastian\MicroFramework\Http;

use Sebastian\MicroFramework\Application;
use Sebastian\MicroFramework\Exceptions\Http\ResponseAlreadySentException;
use Sebastian\MicroFramework\Exceptions\Http\HeadersAlreadySentException;

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
        
    public function set(string|array $nameOrArray, string|array|null $value = null): void { $this->header($nameOrArray, $value); }
    public function header(string|array $nameOrArray, string|array|null $value = null): void {
        if ($this->headersSent)
            throw new HeadersAlreadySentException();

        if (is_array($nameOrArray)) {
            foreach ($nameOrArray as $h => $v) {
                $this->headers[$h] = $v;
            } 
        } else {
            $name = strtolower($nameOrArray);
            $this->headers[$name] = $value;
        }
    }

    public function append(string $name, string|array $value): void {
        if ($this->headersSent)
            throw new HeadersAlreadySentException();   

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

    /* ---------- Cookies ---------- */

    public function cookie(string $name, string $value, array $options = []): void {
        setcookie($name, $value, $options);
    }

    public function clearCookie(string $name, array $options = []) {
        setcookie($name, '', array_merge(['expires'  => time() - 3600], $options));
    }


    /* ---------- Send ---------- */

    public function send(array|string|bool|null $body = null): Response {
        if ($this->ended)
            throw new ResponseAlreadySentException();

        if ($body !== null) {
            if (!$this->hasHeader('Content-Type')) {
                $this->set('Content-Type', $this->inferContentType($body));
                $this->setCharset();
            }

            $this->body = $this->normalizeBody($body);

            if (!$this->hasHeader('Content-Length')) 
                $this->set('Content-Length', strlen($this->body));
        }

        $this->end();
        return $this;
    }

    public function json(array|string|bool|int|null $data): Response {
        $this->body = json_encode($data);
        $this->header('Content-Type', 'application/json');
        return $this->send();
    }

    public function sendStatus(int $statusCode): Response {
        return $this->status($statusCode)->send();
    }

    // TODO: Implement with Swoole later to stream chunks of files
    public function sendFile() {}
    public function download(): void {}

    public function end(?string $data = null, ?callable $callback = null): void {
        if ($this->ended)
            throw new ResponseAlreadySentException();

        if ($data !== null && $this->body === null)
            $this->body = $data;

        if (!$this->headersSent) 
            $this->sendHeaders();

        $this->sendBody();
        $this->ended = True;

        $callback();

        # TODO: close socket once Swoole implemented
    }

    public function ended(): bool { return $this->ended; }

    /* ---------- Send Private Helper Functions ---------- */

    private function inferContentType(array|string|object|bool $body) {
        return match (true) {
            is_array($body), is_object($body) => 'application/json',
            is_bool($body)                   => 'text/plain',
            is_string($body)                 => 'text/plain',
            default                           => 'application/octet-stream',
        };
    }

    private function normalizeBody(array|string|object|bool $body): string {
        if (is_array($body) || is_object($body)) {
            $this->set('Content-Type', 'application/json');
            return json_encode($body, JSON_THROW_ON_ERROR);
        }

        if (is_bool($body)) 
            return $body ? '1' : '0';

        return (string) $body;
    }

    private function setCharset(): void {
        if (!$this->hasHeader('Content-Type')) return;

        $contentType = $this->getHeader('Content-Type');

        if (str_starts_with($contentType, 'text/') || $contentType === 'application/json') {
            if (!str_contains($contentType, 'charset=')) {
                $this->set('Content-Type', $contentType . '; charset=utf-8');
            }
        }
    }

    private function sendHeaders(): void {
        if ($this->headersSent)
            throw new HeadersAlreadySentException();

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

    private function sendBody(): void {
        echo $this->body ?? '';
    } 
}