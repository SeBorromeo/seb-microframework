<?php namespace SeBorromeo\SebMicroframework\Routing;

use SeBorromeo\SebMicroframework\Http\HttpMethod;
use SeBorromeo\SebMicroframework\Http\Request;
use SeBorromeo\SebMicroframework\Http\Response;
use SeBorromeo\PathToRegex\PathToRegex;
use SeBorromeo\PathToRegex\Regex;

const MATCHING_GROUP_REGEXP = '/\((?:\?<(.*?)>)?(?!\?)/';

class Layer {
    public readonly string $name;

    /** @var Closure[] */
    private readonly array $matchers;

    private readonly bool $slash;

    /**
     * @param string|Regex|string[] $path
     * @param array<string, mixed> $options
     */
    public function __construct(
        string|Regex|array $path,
        public readonly array $options = [],
        public readonly mixed $handle = null,
        public readonly ?HttpMethod $method = null,
        public readonly ?Route $route = null,
    ) {
        if ($handle && !is_callable($handle, false, $handle_name)) 
            throw new \InvalidArgumentException('Route handler must be a callable');
        
        $this->name = $handle instanceof \Closure
            ? '<anonymous>'
            : ($handle_name ?: '<anonymous>');

        $this->slash = $path === '/' && $this->options['end'] === false;

        $matcher = function(Regex|string $_path): callable {
            if ($_path instanceof Regex) {
                $keys = [];
                $nameIdx = 0;

                preg_match_all(MATCHING_GROUP_REGEXP, $_path, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

                foreach ($matches as $m) {
                    $keys[] = [
                        'name' => $m[1][0] ?? $nameIdx++,
                        'offset' => $m[0][1],
                    ];
                }
                
                // Regex Matcher
                return function(string $p) use ($keys, $_path): array|false {
                    if (!preg_match($_path, $p, $match)) 
                        return false;

                    $params = [];
                    for ($i = 1; $i < count($match); $i++) {
                        $key = $keys[$i - 1];
                        $prop = $key['name'];
                        $val = self::decodeParam($match[$i]);

                        if ($val !== null) {
                            $params[$prop] = $val;
                        }
                    }
                    
                    return ['params' => $params, 'path' => $match[0]];
                };
            }

            // Matcher using path-to-regex
            return PathToRegex::match($this->options['strict'] ? $_path : self::loosen($_path), [
                'sensitive' => $this->options['sensitive'],
                'end' => $this->options['end'],
                'trailing' => !$this->options['strict'],
                'decode' => [self::class, 'decodeParam'],
            ]);
        };
        $this->matchers = is_array($path) ? array_map($matcher, $path) : [$matcher($path)];
    }

    /**
     * Handle the error for the layer.
     * 
     * @internal
     */
    public function handleError(\Throwable|string $error, Request $req, Response $res, callable $next): void {
        $fn = $this->handle;

        if (self::getNumParams($fn) !== 4) {
            $next($error);
            return;
        }

        try {
            $fn($error, $req, $res, $next);
        } catch(\Exception $err) {
            $next($err);
        }
    }

    /**
     * Handle the request for the layer.
     * 
     * @internal
     */
    public function handleRequest(Request $req, Response $res, callable $next): void {
        $fn = $this->handle;
        
        if (self::getNumParams($fn) > 3) {
            $next();
            return;
        }

        try {
            $fn($req, $res, $next);
        } catch(\Exception $err) {
            $next($err);
        }
    }

    /**
     * Attempt to match the given path against this layer's path pattern. If a match is found, the matched path and extracted parameters are stored in the layer's properties.
     * 
     * @internal
     */
    public function match(string $path): ?MatchResult {
        $match = null;

        if ($this->slash) {
            return new MatchResult(
                params: [],
                path: '',
                keys: []
            );
        }

        $i = 0;
        while (!$match && $i < count($this->matchers)) {
            $match = $this->matchers[$i]($path);
            $i++;
        }

        if (!$match) 
            return null;

        return new MatchResult(
            params: $match['params'],
            path: $match['path'],
            keys: array_keys($match['params'])
        );
    }

    /* ---------- Layer Util ---------- */

    /**
     * Returns the number of params given a callable.
     */
    private static function getNumParams(callable $callable): int {
        $CReflection = is_array($callable) ? 
            new \ReflectionMethod($callable[0], $callable[1]) : 
            new \ReflectionFunction($callable);
        return $CReflection->getNumberOfParameters();
    }

    /**
     * Decode a URL parameter, ensuring it's valid UTF-8. Throws an exception if decoding fails. 
     */
    public static function decodeParam(?string $val): string|null {
        if (!is_string($val) || strlen($val) === 0) 
            return $val;
        
        $decoded = rawurldecode($val);
        if (!mb_check_encoding($decoded, 'UTF-8')) 
           throw new \InvalidArgumentException("Failed to decode param '$val'");

        return $decoded;
    }

    /**
     * Remove trailing slashes from a path, unless it's the root path. Used when strict routing is disabled.
     */
    private static function loosen(array|string $path): string {
        if ($path === '/') 
            return $path;

        return is_array($path) ? array_map([self::class, 'loosen'], $path) : rtrim($path, '/');
    }
}