<?php namespace SeBorromeo\SebMicroframework\Routing;

use SeBorromeo\SebMicroframework\Http\Request;
use SeBorromeo\SebMicroframework\Http\Response;
use SeBorromeo\SebMicroframework\Http\HttpMethod;
use SeBorromeo\PathToRegex\Regex;

class Router {
    /** @var Layer[] */
    private array $stack = [];

    /** @var array<string, callable> */
    private array $params = [];
    
    private bool $caseSensitive;
    private bool $mergeParams;
    private bool $strict;

    public function __construct(
        private array $options = []
    ) {
        $this->caseSensitive = $options['case sensitive'] ?? false;
        $this->mergeParams = $options['merge params'] ?? false;
        $this->strict = $options['strict'] ?? false;
    }

    /**
     * Magic method to turn Router into a callable.
     */
    public function __invoke(Request $request, Response $response, callable $next) {
        $this->handle($request, $response, $next);
    }

    /**
     * Map given param $name to given callback.
     */
    public function param(string $name, callable $fn): Router {
        if (!isset($this->params[$name])) {
            $this->params[$name] = [];
        }

        $this->params[$name][] = $fn;

        return $this;
    }
    /* ---------- HTTP Methods ---------- */

    /**
     * Magic method to handle dynamic HTTP method calls.
     * 
     * @throws BadMethodCallException
     * @throws InvalidArgumentException
     */
    public function __call(string $method, array $args): Router {
        if (is_null(HttpMethod::fromString($method)))
            throw new \BadMethodCallException("Method $method does not exist");
        
        if (count($args) === 0) 
            throw new \InvalidArgumentException('First argument $path is required');

        if (!is_string($args[0]))
            throw new \InvalidArgumentException('First argument $path must be of type string');

        $route = $this->route($args[0]);
        $route->{$method}(...array_slice($args, 1));
        return $this;
    }
}