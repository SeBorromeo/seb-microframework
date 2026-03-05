<?php namespace SeBorromeo\SebMicroframework\Routing;

use SeBorromeo\SebMicroframework\Http\Request;
use SeBorromeo\SebMicroframework\Http\Response;
use SeBorromeo\SebMicroframework\Routing\Layer;
use SeBorromeo\SebMicroframework\Http\HttpMethod;
use SeBorromeo\SebMicroframework\Utils\ArrayHelper;

class Route {
    /** @var Layer[] */
    private array $stack = [];

    /** @var array<string, HttpMethod> */
    private array $methods = [];

    public function __construct(
        public readonly string $path,
    ) {}

    /**
     * Determine if this route handles the given HTTP method.
     * 
     * @internal
     */
    public function handlesMethod(HttpMethod $method): bool {
        if ($this->methods[HttpMethod::ALL->value]) 
            return true;

        if ($method === HttpMethod::HEAD && !$this->methods[HttpMethod::HEAD->value]) {
            $method = HttpMethod::GET;
        }

        return isset($this->methods[$method->value]);
    }

    /**
     * Get the list of HTTP methods that this route handles.
      * 
       * @return HttpMethod[]
       * 
       * @internal
     */
    public function methods(): array {
        return array_values($this->methods);
    }   

    /**
     * Dispatch req, res into this route.
     *
     * @internal
     */
    public function dispatch(Request $req, Response $res, callable $done) {
        $idx = 0;
        $stack = $this->stack;
        $sync = 0;

        if (count($stack) === 0) 
            return $done();

        $method = $req->method;

        if ($method === HttpMethod::HEAD && !$this->methods[HttpMethod::HEAD->value]) {
            $method = HttpMethod::GET;
        }

        $req->setRoute($this);

        $next = function(\Throwable|string|null $err = null) use (&$idx, &$sync, $stack, $method, $req, $res, $done, &$next) {
            // signal to exit route
            if ($err === 'route') 
                return $done();

            // signal to exit router
            if ($err === 'router') 
                return $done($err);

            // no more matching layers
            if ($idx >= count($stack)) 
                return $done($err);

            // max sync stack
            if (++$sync > 100) 
                return $next($err);

            $layer = null;
            $match = null;

            while (!$match && $idx < count($stack)) {
                $layer = $stack[$idx++];
                $match = $layer->method === HttpMethod::ALL || $layer->method === $method;
            }

            if (!$match)
                return $done($err);

            if ($err) {
                $layer->handleError($err, $req, $res, $next);
            } else {
                $layer->handleRequest($req, $res, $next);
            }

            $sync = 0;
        };

        $next();
    }

    /* ---------- HTTP Methods ---------- */

    /**
     * Magic method to handle dynamic HTTP method calls.
     * 
     * @throws BadMethodCallException
     * @throws InvalidArgumentException
     */
    public function __call(string $method, array $args): Route {
        $enum = HttpMethod::fromString($method);

        if (is_null($enum)) 
            throw new \BadMethodCallException("Method $method does not exist.");

        return $this->addMethod($enum, ...$args);
    }

    /**
     * Add a handler or list of handlers for the given HTTP method.
     * 
     * @throws InvalidArgumentException
     */
    private function addMethod(HttpMethod $method, callable|array ...$handlers): Route {
        $callbacks = ArrayHelper::flatten($handlers);

        if (count($callbacks) === 0)
            throw new \InvalidArgumentException('At least one handler required.');

        foreach ($callbacks as $callback) {
            if (!is_callable($callback))
                throw new \InvalidArgumentException('Handlers must be of type callable.');

            $layer = new Layer(
                path: '/', 
                options: [], 
                handle: $callback, 
                method: $method
            );

            $this->methods[$method->value] = $method;
            $this->stack[] = $layer;
        }

        return $this;
    }
}