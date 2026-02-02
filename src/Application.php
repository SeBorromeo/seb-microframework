<?php namespace Sebastian\MicroFramework;

final class Application {
    protected array $settings = [
        'env' => 'development',
        'etag' => 'weak',
        'jsonp callback name' => 'callback',
        'query parser' => 'simple',
        'subdomain offset' => 2,
        'trust proxy' => false,
        'x-powered-by' => true
    ];

    public function __construct() {
        $appEnv = getenv('APP_ENV'); 
        $this->set('env', $appEnv ?: 'development');
        if ($appEnv == 'production')
            $this->set('view cache', true);

        $this->set('views', getcwd() . '/views');
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
}