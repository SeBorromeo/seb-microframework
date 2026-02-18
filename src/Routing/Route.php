<?php namespace Sebastian\MicroFramework\Routing;

use InvalidArgumentException;
use Sebastian\MicroFramework\Http\HttpMethods;
use Sebastian\MicroFramework\Http\Request;
use Sebastian\MicroFramework\Http\Response;
use Sebastian\MicroFramework\Routing\Layer;
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
    public function handlesMethod(string $method): bool {
        if ($this->methods['_all']) 
            return true;

        $method = strtolower($method);

        if ($method === 'head' && !$this->methods['head']) {
            $method = 'get';
        }

        return isset($this->methods[$method]) && $this->methods[$method];
    }

    /**
     * Get the list of HTTP methods that this route handles.
      * 
       * @return string[]
       * 
       * @internal
     */
    public function methods(): array {
        $methods = array_keys($this->methods);
        if ($this->methods['get'] && $this->methods['head']) {
            $methods[] = 'head';
        }

        return array_map('strtoupper', $methods);
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

        $method = strtolower($req->method);

        if ($method === 'head' && !$this->methods['head']) {
            $method = 'get';
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
                $match = !$layer->method || $layer->method === $method;
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
        return $this->addMethod('_all', ...$handlers);
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

        return $this->addMethod($method, ...$args);
    }

    /**
     * Add a handler or list of handlers for the given HTTP method.
     * 
     * @throws InvalidArgumentException
     */
    private function addMethod(string $method, callable|array ...$handlers): Route {
        $callbacks = [];

        array_walk_recursive($handlers, function($h) use (&$callbacks) {
            $callbacks[] = $h;
        });

        if (count($callbacks) === 0)
            throw new \InvalidArgumentException('At least one handler required.');

        foreach ($callbacks as $callback) {
            if (!is_callable($callback))
                throw new \InvalidArgumentException('Handlers must be of type callable.');

            $layer = new Layer('/', [], $callback);
            $layer->method = $method === '_all' ? null: $method;

            $this->methods[$method] = true;
            $this->stack[] = $layer;
        }

        return $this;
    }
}