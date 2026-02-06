<?php namespace Sebastian\MicroFramework\Routing;

const MATCHING_GROUP_REGEXP = '/\((?:\?<(.*?)>)?(?!\?)/g';

class Layer {
    private array $keys = [];
    private $params;
    private bool $slash;
    private string $name;
    private $matcher;

    public function __construct(
        private string $path,
        private array $options,
        private $handle
    ) {
        $this->options = $options ?? [];
        $this->name = $handlers['name'] ?? '<anonymous>';
        $this->slash = $this->path === '/' && $this->options['end'] === false;

        if (!is_callable($handle)) 
            throw new \InvalidArgumentException('Route handler must be a callable');

        $matcher = function(string $_path) {
            $keys = [];
            $nameIdx = 0;

            while (preg_match(MATCHING_GROUP_REGEXP, $_path, $matches)) {
                $keys[] = [
                    'name' => $matches[1] ?? $nameIdx++,
                    'optional' => false
                ];
            }
            
            return function($p) use ($keys, $matches, $_path) {
                $match = preg_match($_path, $p);
                if (!$match) {
                    return false;
                }

                $params = [];
                for ($i = 1; $i <= count($matches); $i++) {
                    $key = $keys[$i - 1];
                    $prop = $key['name'];
                    $val = $this->decodeParam($matches[$i]);

                    if ($val !== null) {
                        $params[$prop] = $val;
                    }
                }
                
                return [$params, 'path' => $matches[0]];
            };
        };
        $this->matcher = is_array($this->path) ? array_map($matcher, $this->path) : [$matcher($this->path)];
    }

    public function path(): string { return $this->path; }
    public function options(): array { return $this->options; }
    public function handle(): mixed { return $this->handle; }

    private function decodeParam(string $val): string|null {
        // TODO:
        return null;
    }
}