<?php

use Sebastian\MicroFramework\Http\Methods;
use Sebastian\MicroFramework\Http\Request;
use Sebastian\MicroFramework\Http\Response;
use Sebastian\MicroFramework\Routing\Layer;

class Route {
    private array $httpMethods;

    public function __construct(
        private string $path,
        private array $stack = [],
        private array $methods = []
    ) {
        foreach(Methods::ALL as $httpMethod) {
            $this->httpMethods[] = strtolower($httpMethod);
        }
    }

    public function path(): string { return $this->path; }

    public function _handlesMethod(string $method): bool {
        if ($this->methods['_all']) {
            return true;
        }

        $method = strtolower($method);

        if ($method === 'head' && !$this->methods['head']) {
            $method = 'get';
        }

        return $this->methods[$method];
    }

    public function _methods(): array {
        $methods = array_keys($this->methods);
        if ($this->methods['get'] && $this->methods['head']) {
            $methods[] = 'head';
        }

        foreach ($methods as &$method) {
            $method = strtoupper($method);
        }
        unset($method);
        
        return $methods;
    }   

    public function _dispatch(Request $req, Response $res, callable $done): void {

    }

    public function all(callable|array ...$handlers): Route {

        
        return $this;
    }

    /* ---------- HTTP Methods ---------- */

    public function __call(string $method, array $args): Route {
        if (!in_array($method, $this->httpMethods)) 
            throw new \BadMethodCallException("Method $method does not exist.");

        return $this->addMethod($method, ...$args);
    }

    private function addMethod(string $method, callable|array ...$handlers): Route {
        if (count($handlers) === 0)
            throw new \InvalidArgumentException('At least one handler required.');

        $callbacks = [];

        foreach ($handlers as $handler) {
            if (is_array($handler)) {
                $callbacks[] = array_merge($handlers, $handler);
            } else {
                $callbacks[] = $handler; 
            }
        }

        foreach ($callbacks as $callback) {
            if (!is_callable($callback))
                throw new \InvalidArgumentException('Handlers must be of type callable.');

            $layer = new Layer('/', [], $callback);
            $layer->method($method);

            $this->methods[$method] = true;
            $this->stack[] = $layer;
        }

        return $this;
    }
}