<?php namespace SeBorromeo\SebMicroframework\Http;

use SeBorromeo\SebMicroframework\Application;
use SeBorromeo\SebMicroframework\Exceptions\Http\ResponseAlreadySentException;
use SeBorromeo\SebMicroframework\Exceptions\Http\HeadersAlreadySentException;
use SeBorromeo\SebMicroframework\Exceptions\View\ViewNotFoundException;
use SeBorromeo\SebMicroframework\Exceptions\Application\InvalidEngineException;

const DEFAULT_RESPONSE_STATUS_TEXTS = [
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

class Response {
    private array $locals = [];
    private array $headers = [];

    private bool $headersSent = False;
    private bool $ended = False;
    private int $statusCode = 200;
    public string $statusMessage = 'OK';
    private string $body = "";

    public readonly Request $req;

    public function __construct(
        public readonly Application $app
    ) {}

    public function setRequest(Request $req): void { $this->req = $req; }

    /* ---------- Headers ---------- */
    
    public function get(string $name): string|array|null { return $this->getHeader($name); }
    public function getHeader(string $name): string|array|null {
        return $this->headers[strtolower($name)];
    }
    
    public function getHeaders(): array {
        return $this->headers;
    }
        
    public function getHeaderNames(): array {
        return array_keys($this->headers);
    }
    
    public function hasHeader($name): bool {
        return isset($this->headers[strtolower($name)]);
    }
        
    public function removeHeader($name): void {
        unset($this->headers[strtolower($name)]);
    }
        
    public function set(string|array $nameOrArray, string|array|null $value = null): void { $this->setHeader($nameOrArray, $value); }
    public function setHeader(string|array $nameOrArray, string|array|null $value = null): void {
        if ($this->headersSent)
            throw new HeadersAlreadySentException();

        if (is_array($nameOrArray)) {
            foreach ($nameOrArray as $h => $v) {
                $this->headers[strtolower($h)] = $v;
            } 
        } else {
            $name = strtolower($nameOrArray);
            $this->headers[$name] = $value;
        }
    }

    public function append(string $name, string|array $value): void {
        if ($this->headersSent)
            throw new HeadersAlreadySentException();   
        
        $name = strtolower($name);

        if (!$this->hasHeader($name)) {
            $this->set($name, $value);
        } else {
            $existing = is_array($this->headers[$name]) ? $this->headers[$name] : [$this->headers[$name]];
            $valuesToAdd = is_array($value) ? $value : [$value];
            $this->headers[$name] = array_merge($existing, $valuesToAdd);
        }
    }

    public function headersSent(): bool { return $this->headersSent; }

    /* ---------- Header Helpers ---------- */

    /**
     * Sets Content-Disposition header field to "attachment". Optional filename can be given, in which content type set based on extension and sets Content-Disposition param "filename=".
     */
    public function attachment(?string $filename = null): void {
        if ($filename) {
            $filename = basename($filename);
            $this->set('Content-Disposition', "attachment; filename=\"$filename\"");
            $this->type(pathinfo($filename, PATHINFO_EXTENSION));
        } else {
            $this->set('Content-Disposition', 'attachment');
        }
    }

    /**
     * Performs content-negotiation on the Accept HTTP header on the request object, when present.
     */
    public function format(array $callbacks): void {
        foreach ($callbacks as $type => $callback) {
            if ($this->req->accepts($type)) {
                $this->type($type);
                $callback();
                return;
            }
        }
        $this->status(406)->send();
    }

    /**
     * Joins param $links to Link header field.
     * 
     * @param array<string, string> $links
     *   - (e.g., ['next' => 'http://api.example.com/users?page=2'])
     */
    public function links(array $links): void {
        $headerLinks = [];
        foreach ($links as $rel => $url) {
            $headerLinks[] = "<$url>; rel=\"$rel\"";
        }
        $this->append('Link', $headerLinks);
    }

    /**
     * Sets Location header.
     */
    public function location(string $path): void {
        $this->set('Location', $path);
    }

    /**
     * Sets Content-Type header. If $type contains "/" character, sets header to exactly $type, otherwise infers MIME type.  
     */
    public function contentType(string $type): void { $this->type($type); }
    public function type(string $type): void {
        $mimes = new \Mimey\MimeTypes;

        $mimeType = str_contains($type, '/') ? $type : ($mimes->getMimeType($type) ?: 'application/octet-stream');
        $this->set('Content-Type', $mimeType);
    }

    /**
     * Adds $field to Vary header if not there already.
     */
    public function vary(string $field): Response {
        $fields = $this->getHeader('Vary') ?? [];
        if (is_string($fields)) {
            $fields = [$fields];
        }

        if (!in_array($field, $fields, true)) {
            $this->append('Vary', $field);
        }

        return $this;
    }


    /* ---------- Status ---------- */

    public function status(int $code): Response {
        $this->statusCode = $code;
        $this->statusMessage = DEFAULT_RESPONSE_STATUS_TEXTS[$code] ?? '';
        return $this;
    }

    public function statusCode(): int { return $this->statusCode; }

    public function statusMessage(): string { return $this->statusMessage; }

    /* ---------- Cookies ---------- */

    public function cookie(string $name, string $value, array $options = []): void {
        setcookie($name, $value, $options);
    }

    public function clearCookie(string $name, array $options = []) {
        setcookie($name, '', array_merge(['expires'  => time() - 3600], $options));
    }

    /* ---------- View ---------- */

    public function render(string $view, ?array $locals = [], ?callable $callback = null): void {
        if ($this->ended) 
            throw new ResponseAlreadySentException();

        foreach ($locals as $key => $val) {
            $this->addLocalVar($key, $val);
        }

        $ext = $this->getExtension($view);
        
        $engine = $this->app->getEngine($ext);

        $path = $this->resolvePath($view, $ext);

        $this->set('Content-Type', 'text/html; charset=utf-8');
        $html = $engine->render($path, $locals);    

        if ($callback) 
            $callback(null, $html);
        else
            $this->send($html);
    }

    private function getExtension(string $view): string {
        if (str_contains($view, '.')) 
            return pathinfo($view, PATHINFO_EXTENSION);

        $viewEngine = $this->app->get('view engine');
        if (!$viewEngine)
            throw new InvalidEngineException();

        return $viewEngine;
    }

    private function resolvePath(string $view, string $ext): string {
        $viewsDir = rtrim($this->app->get('views'), '/');

        if (!$viewsDir)
            throw new \LogicException("Views directory not configured. Set using \$app->set('views', {path}).");

        $viewPath = $view;
        if (!str_ends_with($view, ".$ext")) {
            $viewPath = str_replace('.', '/', $view);
            $viewPath .= ".$ext";
        }

        $fullPath = "$viewsDir/$viewPath";

        if (!file_exists($fullPath)) 
            throw new ViewNotFoundException($fullPath);

        return $fullPath;
    }

    public function locals(): array {
        return $this->locals;
    }

    public function addLocalVar(string $key, string $val): void {
        $this->locals[$key] = $val;
    }

    /* ---------- Send ---------- */

    public function send(array|string|bool|null $body = null): Response {
        if ($this->ended)
            throw new ResponseAlreadySentException();

        if ($body !== null) {
            if (!$this->hasHeader('Content-Type')) {
                $this->set('Content-Type', $this->inferContentType($body));
                $this->setCharsetUTF8();
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
        $this->setHeader('Content-Type', 'application/json');
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

        if ($callback)
            $callback();

        # TODO: close socket once Swoole implemented
    }

    public function ended(): bool { return $this->ended; }

    /* ---------- Send Private Helper Functions ---------- */

    private function inferContentType(array|string|object|bool $body) {
        return match (true) {
            is_array($body), is_object($body) => 'application/json',
            is_bool($body)                    => 'text/plain',
            is_string($body)                  => 'text/plain',
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

    private function setCharsetUTF8(): void {
        if (!$this->hasHeader('Content-Type')) return;

        $contentType = $this->getHeader('Content-Type');

        if (is_string($contentType) && (str_starts_with($contentType, 'text/') || $contentType === 'application/json')) {
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