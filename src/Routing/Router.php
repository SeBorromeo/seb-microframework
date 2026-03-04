<?php namespace SeBorromeo\SebMicroframework\Routing;

use SeBorromeo\SebMicroframework\Http\Request;
use SeBorromeo\SebMicroframework\Http\Response;
use SeBorromeo\SebMicroframework\Http\HttpMethod;
use SeBorromeo\SebMicroframework\Utils\ArrayHelper;
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

    /**
     * 
     */
    public function handle(Request $req, Response $res, callable $callback): void {
        $idx = 0;
        $methods;
        $protohost = $this->getProtohost($req->url);
        $removed = '';
        $slashAdded = false;
        $sync = 0;
        $paramCalled = [];

        $parentParams = $req->params();
        $parentURL = $req->baseUrl ?? '';
        $done = self::restore($callback, $req, 'baseUrl', 'next', 'params');

        $trimPrefix = null;
        $next = function(\Throwable|string|null $err = null) use (&$trimPrefix, &$next) {

        };

        $trimPrefix = function($layer, \Throwable|string|null $layerError, string $layerPath, string $path) use ($req, $res, &$next, $parentURL, $protohost, &$slashAdded): void {
            if (strlen($layerPath) !== 0) {
                // Validate path is a prefix match
                if ($layerPath !== substr($path, 0, strlen($layerPath))) {
                    next($layerError);
                    return;
                }

                // Validate path breaks on a path separator
                $c = $path[strlen($layerPath)];
                if ($c && $c !== '/') { 
                    next($layerError);
                    return;
                }

                // Trim off the part of the url that matches the route
                // middleware (.use stuff) needs to have the path stripped
                $removed = $layerPath;
                $req->url = $protohost + substr($req->url, 0, strlen($protohost) + strlen($removed));

                // Ensure leading slash
                if (!$protohost && $req->url[0] !== '/') {
                    $req->url = '/' + $req->url;
                    $slashAdded = true;
                }

                // Setup base URL (no trailing slash)
                $req->baseUrl = $parentURL + ($removed[strlen($removed) - 1] === '/'
                    ? substr($removed, 0, strlen($removed) - 1)
                    : $removed);
            }

            if ($layerError) {
                $layer->handleError($layerError, $req, $res, $next);
            } else {
                $layer->handleRequest($req, $res, $next);
            }
        };
        
        $next();
    }

    /**
     * Register middleware on the router.
     *
     * If the first argument is a string, it is treated as the mount path.
     * Otherwise, the middleware is mounted at "/".
     * 
     * @param string|callable|array $pathOrHandler  
     *    - The mount path, first middleware handler, or array of middleware handlers.
     * 
     * @param array|callable $args
     *    - Additional middleware handlers.
     */
    public function use(string|callable|array $pathOrHandler, array|callable ...$args): Router {
        $path = $pathOrHandler;
        $callbacks = ArrayHelper::flatten($args);

        if (is_callable($pathOrHandler)) {
            $path = '/';
            $callbacks = array_merge([$pathOrHandler], $callbacks);
        }

        if (count($callbacks) === 0)
            throw new \InvalidArgumentException('Argument $handler is required.');

        foreach ($callbacks as $callback) {
            if (!is_callable($callbacks))
                throw new \InvalidArgumentException('Handlers must be of type callable.');

            $layer = new Layer(
                path: $path, 
                handle: $callback,
                options: [
                    'sensitive' => $this->caseSensitive,
                    'strict' => false,
                    'end' => false,
                ]
            );

            $this->stack[] = $layer;
        }
        
        return $this;
    }

    /**
     * Create a new route for the given path.
     */
    public function route(Regex|string $path): Route {
        $route = new Route($path);

        $handle = function($req, $res, $next) use ($route) { $route->dispatch($req, $res, $next); };
        
        $layer = new Layer(
            path: $path, 
            handle: $handle,
            route: $route,
            options: [
                'sensitive' => $this->caseSensitive,
                'strict' => $this->strict,
                'end' => true,
            ], 
        );

        $this->stack[] = $layer;
        return $route;
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