<?php namespace Sebastian\MicroFramework\Routing;

use Sebastian\MicroFramework\Routing\Lib\PathUtils;
use Sebastian\MicroFramework\Routing\Lib\Regex;

const MATCHING_GROUP_REGEXP = '/\((?:\?<(.*?)>)?(?!\?)/';

class Layer {
    public array $keys = [];
    public ?array $params = [];
    public ?string $path = null;
    public readonly string $name;
    
    private bool $slash;
    private $matchers;

    public function __construct(
        Regex|array|string $path,
        public readonly array $options = [],
        public readonly mixed $handle = null,
    ) {
        $this->slash = $path === '/' && $this->options['end'] === false;

        if ($handle && !is_callable($handle, false, $handle_name)) 
            throw new \InvalidArgumentException('Route handler must be a callable');
        
        $this->name = $handle instanceof \Closure
            ? '<anonymous>'
            : ($handle_name ?: '<anonymous>');

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
            return PathUtils::match($this->options['strict'] ? $_path : self::loosen($_path), [
                'sensitive' => $this->options['sensitive'],
                'end' => $this->options['end'],
                'trailing' => !$this->options['strict'],
                'decode' => [self::class, 'decodeParam'],
            ]);
        };
        $this->matchers = is_array($path) ? array_map($matcher, $path) : [$matcher($path)];
    }

    /**
     * Attempt to match the given path against this layer's path pattern. If a match is found, the matched path and extracted parameters are stored in the layer's properties.
     * 
     * @param string $path
     *  - The request path to match against this layer's pattern.
     * 
     * @return bool
     *  - True if the path matches this layer's pattern, false otherwise. If true, the matched path and parameters are stored in $this->path and $this->params.
     * 
     * @internal
     */
    public function match(string $path): bool {
        $match = null;

        if ($this->slash) {
            $this->params = [];
            $this->path = '';
            return true;
        }

        $i = 0;
        while (!$match && $i < count($this->matchers)) {
            $match = $this->matchers[$i]($path);
            $i++;
        }

        if (!$match) {
            $this->params = null;
            $this->path = null;
            return false;
        }

        $this->params = $match['params'];
        $this->path = $match['path'];
        $this->keys = array_keys($match['params']);

        return true;
    }

    /* ---------- Layer Util ---------- */

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
    public static function loosen(array|string $path): string {
        if ($path === '/') {
            return $path;
        }

        return is_array($path) ? array_map([self::class, 'loosen'], $path) : rtrim($path, '/');
    }
}