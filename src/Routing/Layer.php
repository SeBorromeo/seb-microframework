<?php namespace Sebastian\MicroFramework\Routing;

use Sebastian\MicroFramework\Routing\Lib\PathUtils;
use Sebastian\MicroFramework\Routing\Lib\Regex;

const MATCHING_GROUP_REGEXP = '/\((?:\?<(.*?)>)?(?!\?)/';

class Layer {
    private array $keys = [];
    private $params;
    private bool $slash;
    public readonly string $name;
    private $matchers;

    public string $method;
    public Route $route;

    public function __construct(
        public readonly string $path,
        public readonly array $options,
        public readonly mixed $handle
    ) {
        $this->options = $options ?? [];
        $this->slash = $this->path === '/' && $this->options['end'] === false;
        
        if (!is_callable($handle, false, $handle_name)) 
            throw new \InvalidArgumentException('Route handler must be a callable');

        $this->name = $handle_name ?? '<anonymous>';

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
            return PathUtils::match($this->options['strict'] ? $this->path : self::loosen($this->path), [
                'sensitive' => $this->options['sensitive'],
                'end' => $this->options['end'],
                'trailing' => !$this->options['strict'],
                'decode' => [PathUtils::class, 'decodeParam'],
            ]);
        };
        $this->matchers = is_array($this->path) ? array_map($matcher, $this->path) : [$matcher($this->path)];
    }

    /* ---------- Layer Util ---------- */

    /**
     * Decode a URL parameter, ensuring it's valid UTF-8. Throws an exception if decoding fails.
     */
    public static function decodeParam(string $val): string {
        $decoded = rawurldecode($val);
        if (!mb_check_encoding($decoded, 'UTF-8')) 
           throw new \InvalidArgumentException("Failed to decode param '$val'");

        return $decoded;
    }

    /**
     * Remove trailing slashes from a path, unless it's the root path. Used when strict routing is disabled.
     */
    public static function loosen(array|string $path): string {
        if ($path === '/') {
            return $path;
        }

        return is_array($path) ? array_map([self::class, 'loosen'], $path) : rtrim($path, '/');
    }
}