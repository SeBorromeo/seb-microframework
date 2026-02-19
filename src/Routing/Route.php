<?php namespace SeBorromeo\SebMicroframework\Routing;

use SeBorromeo\SebMicroframework\Http\HttpMethods;
use SeBorromeo\SebMicroframework\Http\Request;
use SeBorromeo\SebMicroframework\Http\Response;
use SeBorromeo\SebMicroframework\Routing\Layer;
use SeBorromeo\SebMicroframework\Http\HttpMethod;
use SeBorromeo\PathToRegex\Regex;

class Route {
    /** @var Layer[] */
    private array $stack = [];
    private array $methods = [];

    public function __construct(
        public readonly Regex|string $path,
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

        return isset($this->methods[$method->value]) && $this->methods[$method->value];
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

        $req->route = $this;

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

    /**
     * Add a handler or list of handlers for all HTTP methods.
     */
    public function all(callable|array ...$handlers): Route {
        return $this->addMethod(HttpMethod::ALL, ...$handlers);
    }

    /* ---------- HTTP Methods ---------- */

    /**
     * Magic method to handle dynamic HTTP method calls.
     * 
     * @throws BadMethodCallException
     * @throws InvalidArgumentException
     */
    public function __call(string $method, array $args): Route {
        if (!in_array($method, HttpMethods::all())) 
            throw new \BadMethodCallException("Method $method does not exist.");

        return $this->addMethod(HttpMethod::fromString($method), ...$args);
    }

    /**
     * Add a handler or list of handlers for the given HTTP method.
     * 
     * @throws InvalidArgumentException
     */
    private function addMethod(HttpMethod $method, callable|array ...$handlers): Route {
        $callbacks = [];

        array_walk_recursive($handlers, function($h) use (&$callbacks) {
            $callbacks[] = $h;
        });

        if (count($callbacks) === 0)
            throw new \InvalidArgumentException('At least one handler required.');

        foreach ($callbacks as $callback) {
            if (!is_callable($callback))
                throw new \InvalidArgumentException('Handlers must be of type callable.');


            $layer = new Layer('/', [], $callback, $method);

            $this->methods[$method->value] = $method;
            $this->stack[] = $layer;
        }

        return $this;
    }
}