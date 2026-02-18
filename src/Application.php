<?php namespace SeBorromeo\SebMicroframework;

use SeBorromeo\SebMicroframework\Exceptions\Application\InvalidEngineException;
use SeBorromeo\SebMicroframework\Routing\Router;
use SeBorromeo\SebMicroframework\View\Engine\EngineInterface;
use SeBorromeo\SebMicroframework\View\Engine\PhpEngine;
use SeBorromeo\SebMicroframework\View\Engine\PugEngine;

class Application {
    private array $locals = [];
    private array $settings = [
        'env' => 'development',
        'etag' => 'weak',
        'jsonp callback name' => 'callback',
        'query parser' => 'simple',
        'subdomain offset' => 2,
        'trust proxy' => false,
        'x-powered-by' => true
    ];

    private array $viewEngines;
    private array $viewEngineInstances = [];

    public function __construct() {
        $appEnv = getenv('APP_ENV'); 
        $this->set('env', $appEnv ?: 'development');
        if ($appEnv == 'production')
            $this->set('view cache', true);

        $this->set('views', getcwd() . '/views');

        $this->viewEngines = [
            'pug' => PugEngine::class,
            'php' => PhpEngine::class,
        ];
    }

    /* ---------- Settings ---------- */

    public function set(string $name, mixed $value): void {
        $this->settings[$name] = $value;
    }

    public function get(string $name, mixed $default = null): mixed {
        return $this->settings[$name] ?? $default;
    }  

    public function disable(string $name): void {
        $this->set($name, false);
    }

    public function disabled(string $name): bool {
        return $this->get($name) == false;
    }

    public function enable(string $name): void {
        $this->set($name, true);
    }

    public function enabled(string $name): bool {
        return $this->get($name) == true;
    }

    /* ---------- View ---------- */

    // TODO: Move render functionality from Response to here.
    /* Express Docs:
        Think of app.render() as a utility function for generating rendered view strings. 
        Internally res.render() uses app.render() to render views.
    */
    public function render(string $view, ?array $locals = [], ?callable $callback = null): string {
        return '';
    }

    public function locals(): array {
        return $this->locals;
    }

    public function addLocalVar(string $key, string $val): void {
        $this->locals[$key] = $val;
    }

    /* ---------- View Engines ---------- */

    public function engine(string $ext, callable $engine): void {
        $this->viewEngines[$ext] = $engine;
    }

    public function getEngine(string $ext): EngineInterface {
        if (isset($this->viewEngineInstances[$ext])) 
            return $this->viewEngineInstances[$ext];

        if (!isset($this->viewEngines[$ext])) 
            throw new InvalidEngineException($ext);

        $engine = $this->viewEngines[$ext];
        
        if (is_callable($engine)) {
            $this->viewEngineInstances[$ext] = $engine();
        } elseif (is_string($engine)) {
            $this->viewEngineInstances[$ext] = new $engine();
        }
        
        return $this->viewEngineInstances[$ext];
    }
}